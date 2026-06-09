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
}
