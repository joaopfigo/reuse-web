<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class PontosRepo
{
    public static function extrato(int $usuarioId): array
    {
        $stmt = db()->prepare('SELECT * FROM transacoes_pontos WHERE usuario_id = :usuario_id ORDER BY criado_em DESC');
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }
}
