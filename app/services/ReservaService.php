<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/ReservaRepo.php';

class ReservaService
{
    public static function reservar(int $itemId, int $receptoraId): int
    {
        return ReservaRepo::criar($itemId, $receptoraId);
    }
}
