<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class ReservaRepo
{
    public static function criar(int $itemId, int $receptoraId): int
    {
        $stmt = db()->prepare('INSERT INTO reservas (item_id, receptora_id, expira_em) VALUES (:item_id, :receptora_id, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
        $stmt->execute([
            ':item_id' => $itemId,
            ':receptora_id' => $receptoraId,
        ]);

        return (int) db()->lastInsertId();
    }
}
