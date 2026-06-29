<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class AvaliacaoRepo
{
    public static function jaAvaliou(int $reservaId, int $avaliadoraId): bool
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM avaliacoes
             WHERE reserva_id = :reserva_id AND avaliadora_id = :avaliadora_id'
        );
        $stmt->execute([':reserva_id' => $reservaId, ':avaliadora_id' => $avaliadoraId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function criar(int $reservaId, int $avaliadoraId, int $avaliadaId, int $nota, string $comentario): void
    {
        $stmt = db()->prepare(
            'INSERT INTO avaliacoes (reserva_id, avaliadora_id, avaliada_id, nota, comentario)
             VALUES (:reserva_id, :avaliadora_id, :avaliada_id, :nota, :comentario)'
        );
        $stmt->execute([
            ':reserva_id'    => $reservaId,
            ':avaliadora_id' => $avaliadoraId,
            ':avaliada_id'   => $avaliadaId,
            ':nota'          => $nota,
            ':comentario'    => $comentario,
        ]);
    }

    public static function mediaDaUsuaria(int $usuarioId): ?float
    {
        $stmt = db()->prepare('SELECT AVG(nota) FROM avaliacoes WHERE avaliada_id = :id');
        $stmt->execute([':id' => $usuarioId]);
        $media = $stmt->fetchColumn();
        return ($media !== false && $media !== null) ? round((float) $media, 1) : null;
    }

    public static function recebidas(int $usuarioId, int $limite = 8): array
    {
        $limite = max(1, min(20, $limite));

        $stmt = db()->prepare(
            'SELECT a.nota, a.comentario, a.criada_em, u.nome AS avaliadora, i.titulo AS item_titulo
             FROM avaliacoes a
             JOIN usuarios u ON u.id = a.avaliadora_id
             JOIN reservas r ON r.id = a.reserva_id
             JOIN itens i ON i.id = r.item_id
             WHERE a.avaliada_id = :usuario_id
             ORDER BY a.criada_em DESC
             LIMIT ' . $limite
        );
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public static function positivas(int $usuarioId, int $limite = 6): array
    {
        $limite = max(1, min(20, $limite));

        $stmt = db()->prepare(
            'SELECT a.nota, a.comentario, a.criada_em, u.nome AS avaliadora, i.titulo AS item_titulo
             FROM avaliacoes a
             JOIN usuarios u ON u.id = a.avaliadora_id
             JOIN reservas r ON r.id = a.reserva_id
             JOIN itens i ON i.id = r.item_id
             WHERE a.avaliada_id = :usuario_id
               AND a.nota >= 4
             ORDER BY a.criada_em DESC
             LIMIT ' . $limite
        );
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }
}
