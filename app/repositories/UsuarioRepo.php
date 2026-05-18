<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class UsuarioRepo
{
    public static function criar(string $nome, string $email, string $senhaHash, ?string $telefone, string $bairro): int
    {
        $sql = 'INSERT INTO usuarios (nome, email, senha_hash, telefone, bairro)
                VALUES (:nome, :email, :senha_hash, :telefone, :bairro)';

        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':senha_hash' => $senhaHash,
            ':telefone' => $telefone,
            ':bairro' => $bairro,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function buscarPorEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = :email AND ativo = 1');
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = db()->prepare('SELECT id, nome, email, telefone, bairro, cidade, saldo_pontos FROM usuarios WHERE id = :id AND ativo = 1');
        $stmt->execute([':id' => $id]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public static function atualizarPerfil(int $id, string $nome, ?string $telefone, string $bairro, string $cidade): void
    {
        $sql = 'UPDATE usuarios
                SET nome = :nome, telefone = :telefone, bairro = :bairro, cidade = :cidade, atualizado_em = NOW()
                WHERE id = :id';

        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nome' => $nome,
            ':telefone' => $telefone,
            ':bairro' => $bairro,
            ':cidade' => $cidade,
        ]);
    }
}
