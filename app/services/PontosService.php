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
                      AND r.status = "aceita"
                      AND i.status = "reservado"
                    FOR UPDATE';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':reserva_id' => $reservaId]);
            $reserva = $stmt->fetch();

            if (!$reserva) {
                throw new RuntimeException('Reserva invalida, ja confirmada ou fora do estado esperado.');
            }

            $stmtDuplicidade = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM transacoes_pontos
                 WHERE reserva_id = :reserva_id
                   AND tipo IN ("credito", "debito")'
            );
            $stmtDuplicidade->execute([':reserva_id' => $reservaId]);

            if ((int) $stmtDuplicidade->fetchColumn() > 0) {
                throw new RuntimeException('Esta entrega ja possui movimentacao de pontos registrada.');
            }

            $stmtUsuarios = $pdo->prepare(
                'SELECT id, saldo_pontos
                 FROM usuarios
                 WHERE id IN (:doadora_id, :receptora_id)
                 FOR UPDATE'
            );
            $stmtUsuarios->execute([
                ':doadora_id' => $reserva['doadora_id'],
                ':receptora_id' => $reserva['receptora_id'],
            ]);
            $usuarios = [];
            foreach ($stmtUsuarios->fetchAll() as $usuario) {
                $usuarios[(int) $usuario['id']] = $usuario;
            }

            if (!isset($usuarios[(int) $reserva['doadora_id']], $usuarios[(int) $reserva['receptora_id']])) {
                throw new RuntimeException('Participantes da reserva nao encontrados.');
            }

            if ((int) $usuarios[(int) $reserva['receptora_id']]['saldo_pontos'] < (int) $reserva['pontos']) {
                throw new RuntimeException('Saldo insuficiente para confirmar esta entrega.');
            }

            $stmtReserva = $pdo->prepare(
                'UPDATE reservas
                 SET status = "entregue", atualizada_em = NOW()
                 WHERE id = :id AND status = "aceita"'
            );
            $stmtReserva->execute([':id' => $reservaId]);

            $stmtItem = $pdo->prepare(
                'UPDATE itens
                 SET status = "entregue", atualizado_em = NOW()
                 WHERE id = :id AND status = "reservado"'
            );
            $stmtItem->execute([':id' => $reserva['item_id']]);

            if ($stmtReserva->rowCount() !== 1 || $stmtItem->rowCount() !== 1) {
                throw new RuntimeException('Nao foi possivel confirmar a entrega no estado atual.');
            }

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
