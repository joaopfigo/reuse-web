<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class NotificacaoRepo
{
    public static function criar(int $usuarioId, string $tipo, string $mensagem, ?int $reservaId = null): void
    {
        $stmt = db()->prepare(
            'INSERT INTO notificacoes (usuario_id, tipo, mensagem, reserva_id)
             VALUES (:usuario_id, :tipo, :mensagem, :reserva_id)'
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':tipo'       => $tipo,
            ':mensagem'   => $mensagem,
            ':reserva_id' => $reservaId,
        ]);
    }

    public static function naoLidas(int $usuarioId): int
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :id AND lida = 0'
        );
        $stmt->execute([':id' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public static function listar(int $usuarioId): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM notificacoes WHERE usuario_id = :id ORDER BY criada_em DESC LIMIT 50'
        );
        $stmt->execute([':id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public static function marcarTodasLidas(int $usuarioId): void
    {
        $stmt = db()->prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = :id AND lida = 0');
        $stmt->execute([':id' => $usuarioId]);
    }
}
