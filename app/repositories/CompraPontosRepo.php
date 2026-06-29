<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class CompraPontosRepo
{
    public static function criar(int $usuarioId, array $valores): int
    {
        $referencia = 'reuse_points_' . $usuarioId . '_' . bin2hex(random_bytes(10));

        $stmt = db()->prepare(
            'INSERT INTO compras_pontos
                (usuario_id, quantidade_pontos, valor_base, taxa, valor_total, status, referencia_externa)
             VALUES
                (:usuario_id, :quantidade_pontos, :valor_base, :taxa, :valor_total, "criada", :referencia_externa)'
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':quantidade_pontos' => $valores['quantidade_pontos'],
            ':valor_base' => $valores['valor_base'],
            ':taxa' => $valores['taxa'],
            ':valor_total' => $valores['valor_total'],
            ':referencia_externa' => $referencia,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM compras_pontos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $compra = $stmt->fetch();

        return $compra ?: null;
    }

    public static function buscarPorIdEUsuario(int $id, int $usuarioId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM compras_pontos WHERE id = :id AND usuario_id = :usuario_id');
        $stmt->execute([':id' => $id, ':usuario_id' => $usuarioId]);
        $compra = $stmt->fetch();

        return $compra ?: null;
    }

    public static function listarPorUsuario(int $usuarioId, int $limite = 8): array
    {
        $limite = max(1, min(20, $limite));
        $stmt = db()->prepare(
            'SELECT *
             FROM compras_pontos
             WHERE usuario_id = :usuario_id
             ORDER BY criado_em DESC
             LIMIT ' . $limite
        );
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public static function atualizarPreferencia(int $id, array $preferencia): void
    {
        $stmt = db()->prepare(
            'UPDATE compras_pontos
             SET mercado_pago_preference_id = :preference_id,
                 mercado_pago_init_point = :init_point,
                 mercado_pago_sandbox_init_point = :sandbox_init_point,
                 status = "pendente",
                 atualizado_em = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $id,
            ':preference_id' => $preferencia['id'] ?? null,
            ':init_point' => $preferencia['init_point'] ?? null,
            ':sandbox_init_point' => $preferencia['sandbox_init_point'] ?? null,
        ]);
    }

    public static function marcarErro(int $id, string $mensagem): void
    {
        $stmt = db()->prepare(
            'UPDATE compras_pontos
             SET status = "erro",
                 mercado_pago_status_detail = :mensagem,
                 atualizado_em = NOW()
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id, ':mensagem' => substr($mensagem, 0, 180)]);
    }

    public static function processarPagamentoMercadoPago(array $pagamento): ?array
    {
        $referencia = (string) ($pagamento['external_reference'] ?? '');
        $metadataCompraId = (int) ($pagamento['metadata']['compra_pontos_id'] ?? 0);

        if ($referencia === '' && $metadataCompraId <= 0) {
            return null;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            if ($referencia !== '') {
                $stmt = $pdo->prepare('SELECT * FROM compras_pontos WHERE referencia_externa = :referencia FOR UPDATE');
                $stmt->execute([':referencia' => $referencia]);
            } else {
                $stmt = $pdo->prepare('SELECT * FROM compras_pontos WHERE id = :id FOR UPDATE');
                $stmt->execute([':id' => $metadataCompraId]);
            }

            $compra = $stmt->fetch();
            if (!$compra) {
                $pdo->rollBack();
                return null;
            }

            $statusPagamento = (string) ($pagamento['status'] ?? 'unknown');
            $statusCompra = self::statusCompra($statusPagamento);
            if ($compra['status'] === 'aprovado' && $statusCompra !== 'aprovado') {
                $statusCompra = 'aprovado';
            }
            $paymentId = isset($pagamento['id']) ? (string) $pagamento['id'] : null;
            $statusDetail = isset($pagamento['status_detail']) ? (string) $pagamento['status_detail'] : null;

            $stmtUpdate = $pdo->prepare(
                'UPDATE compras_pontos
                 SET status = :status,
                     mercado_pago_payment_id = COALESCE(:payment_id, mercado_pago_payment_id),
                     mercado_pago_status = :mercado_pago_status,
                     mercado_pago_status_detail = :status_detail,
                     aprovado_em = CASE WHEN :status_aprovado = "aprovado" THEN COALESCE(aprovado_em, NOW()) ELSE aprovado_em END,
                     atualizado_em = NOW()
                 WHERE id = :id'
            );
            $stmtUpdate->execute([
                ':id' => $compra['id'],
                ':status' => $statusCompra,
                ':payment_id' => $paymentId,
                ':mercado_pago_status' => $statusPagamento,
                ':status_detail' => $statusDetail,
                ':status_aprovado' => $statusCompra,
            ]);

            if ($statusCompra === 'aprovado' && $compra['status'] !== 'aprovado') {
                $temCompraPontosId = self::transacoesTemCompraPontosId($pdo);
                if ($temCompraPontosId) {
                    $stmtDuplicidade = $pdo->prepare(
                        'SELECT COUNT(*)
                         FROM transacoes_pontos
                         WHERE compra_pontos_id = :compra_pontos_id
                           AND tipo = "credito"'
                    );
                    $stmtDuplicidade->execute([':compra_pontos_id' => $compra['id']]);
                } else {
                    $stmtDuplicidade = $pdo->prepare(
                        'SELECT COUNT(*)
                         FROM transacoes_pontos
                         WHERE usuario_id = :usuario_id
                           AND tipo = "credito"
                           AND motivo = :motivo'
                    );
                    $stmtDuplicidade->execute([
                        ':usuario_id' => $compra['usuario_id'],
                        ':motivo' => 'Compra de pontos aprovada #' . $compra['id'],
                    ]);
                }

                if ((int) $stmtDuplicidade->fetchColumn() === 0) {
                    $pdo->prepare('UPDATE usuarios SET saldo_pontos = saldo_pontos + :pontos WHERE id = :id')
                        ->execute([
                            ':pontos' => $compra['quantidade_pontos'],
                            ':id' => $compra['usuario_id'],
                        ]);

                    if ($temCompraPontosId) {
                        $transacao = $pdo->prepare(
                            'INSERT INTO transacoes_pontos (usuario_id, compra_pontos_id, tipo, quantidade, motivo)
                             VALUES (:usuario_id, :compra_pontos_id, "credito", :quantidade, :motivo)'
                        );
                        $transacao->execute([
                            ':usuario_id' => $compra['usuario_id'],
                            ':compra_pontos_id' => $compra['id'],
                            ':quantidade' => $compra['quantidade_pontos'],
                            ':motivo' => 'Compra de pontos aprovada',
                        ]);
                    } else {
                        $transacao = $pdo->prepare(
                            'INSERT INTO transacoes_pontos (usuario_id, tipo, quantidade, motivo)
                             VALUES (:usuario_id, "credito", :quantidade, :motivo)'
                        );
                        $transacao->execute([
                            ':usuario_id' => $compra['usuario_id'],
                            ':quantidade' => $compra['quantidade_pontos'],
                            ':motivo' => 'Compra de pontos aprovada #' . $compra['id'],
                        ]);
                    }
                }
            }

            $pdo->commit();

            return self::buscarPorId((int) $compra['id']);
        } catch (Throwable $erro) {
            $pdo->rollBack();
            throw $erro;
        }
    }

    public static function statusLabel(string $status): string
    {
        return [
            'criada' => 'Criada',
            'pendente' => 'Pendente',
            'aprovado' => 'Aprovada',
            'recusado' => 'Recusada',
            'cancelado' => 'Cancelada',
            'estornado' => 'Estornada',
            'erro' => 'Erro',
        ][$status] ?? ucfirst($status);
    }

    private static function statusCompra(string $statusMercadoPago): string
    {
        return match ($statusMercadoPago) {
            'approved' => 'aprovado',
            'pending', 'in_process', 'in_mediation', 'authorized' => 'pendente',
            'rejected' => 'recusado',
            'cancelled' => 'cancelado',
            'refunded', 'charged_back' => 'estornado',
            default => 'pendente',
        };
    }

    private static function transacoesTemCompraPontosId(PDO $pdo): bool
    {
        static $temColuna = null;
        if ($temColuna !== null) {
            return $temColuna;
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM transacoes_pontos LIKE 'compra_pontos_id'");
        $temColuna = (bool) $stmt->fetch();

        return $temColuna;
    }
}
