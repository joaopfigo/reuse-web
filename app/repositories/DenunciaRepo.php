<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class DenunciaRepo
{
    public static function criar(
        int $denuncianteId,
        string $motivo,
        string $descricao,
        ?int $itemId = null,
        ?int $reservaId = null,
        ?int $denunciadaId = null
    ): void {
        $stmt = db()->prepare(
            'INSERT INTO denuncias (denunciante_id, item_id, reserva_id, denunciada_id, motivo, descricao)
             VALUES (:denunciante_id, :item_id, :reserva_id, :denunciada_id, :motivo, :descricao)'
        );
        $stmt->execute([
            ':denunciante_id' => $denuncianteId,
            ':item_id'        => $itemId,
            ':reserva_id'     => $reservaId,
            ':denunciada_id'  => $denunciadaId,
            ':motivo'         => $motivo,
            ':descricao'      => $descricao,
        ]);
    }
}
