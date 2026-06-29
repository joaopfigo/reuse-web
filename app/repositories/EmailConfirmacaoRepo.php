<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class EmailConfirmacaoRepo
{
    public static function criar(int $usuarioId): string
    {
        self::invalidarPendentes($usuarioId);

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);

        $stmt = db()->prepare(
            'INSERT INTO email_confirmacoes (usuario_id, token_hash, expira_em)
             VALUES (:usuario_id, :token_hash, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token_hash' => $hash,
        ]);

        return $token;
    }

    public static function buscarValido(string $token): ?array
    {
        $stmt = db()->prepare(
            'SELECT ec.*, u.email, u.nome
             FROM email_confirmacoes ec
             JOIN usuarios u ON u.id = ec.usuario_id
             WHERE ec.token_hash = :token_hash
               AND ec.usado = 0
               AND ec.expira_em >= NOW()
               AND u.ativo = 1'
        );
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
        $registro = $stmt->fetch();

        return $registro ?: null;
    }

    public static function marcarUsado(int $id): void
    {
        $stmt = db()->prepare('UPDATE email_confirmacoes SET usado = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function invalidarPendentes(int $usuarioId): void
    {
        $stmt = db()->prepare(
            'UPDATE email_confirmacoes
             SET usado = 1
             WHERE usuario_id = :usuario_id AND usado = 0'
        );
        $stmt->execute([':usuario_id' => $usuarioId]);
    }
}
