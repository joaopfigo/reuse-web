<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class PontosService
{
    public static function confirmarEntrega(int $reservaId): void
    {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $sql = 'SELECT r.id, r.item_id, r.receptora_id, i.doadora_id, i.pontos
                    FROM reservas r
                    JOIN itens i ON i.id = r.item_id
                    WHERE r.id = :reserva_id
                    FOR UPDATE';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':reserva_id' => $reservaId]);
            $reserva = $stmt->fetch();

            if (!$reserva) {
                throw new RuntimeException('Reserva nao encontrada.');
            }

            $pdo->prepare('UPDATE reservas SET status = "entregue", atualizada_em = NOW() WHERE id = :id')
                ->execute([':id' => $reservaId]);
            $pdo->prepare('UPDATE itens SET status = "entregue", atualizado_em = NOW() WHERE id = :id')
                ->execute([':id' => $reserva['item_id']]);

            $pdo->prepare('UPDATE usuarios SET saldo_pontos = saldo_pontos + :pontos WHERE id = :id')
                ->execute([':pontos' => $reserva['pontos'], ':id' => $reserva['doadora_id']]);
            $pdo->prepare('UPDATE usuarios SET saldo_pontos = GREATEST(saldo_pontos - :pontos, 0) WHERE id = :id')
                ->execute([':pontos' => $reserva['pontos'], ':id' => $reserva['receptora_id']]);

            $transacao = $pdo->prepare('INSERT INTO transacoes_pontos (usuario_id, reserva_id, tipo, quantidade, motivo)
                                        VALUES (:usuario_id, :reserva_id, :tipo, :quantidade, :motivo)');
            $transacao->execute([
                ':usuario_id' => $reserva['doadora_id'],
                ':reserva_id' => $reservaId,
                ':tipo' => 'credito',
                ':quantidade' => $reserva['pontos'],
                ':motivo' => 'Credito por entrega confirmada',
            ]);
            $transacao->execute([
                ':usuario_id' => $reserva['receptora_id'],
                ':reserva_id' => $reservaId,
                ':tipo' => 'debito',
                ':quantidade' => $reserva['pontos'],
                ':motivo' => 'Debito por recebimento confirmado',
            ]);

            $pdo->commit();
        } catch (Throwable $erro) {
            $pdo->rollBack();
            throw $erro;
        }
    }
}
