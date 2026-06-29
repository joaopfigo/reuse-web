<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/validar.php';

class UsuarioRepo
{
    public static function criar(string $nome, string $email, string $senhaHash, ?string $telefone, ?string $telefoneNormalizado, string $bairro, string $cidade): int
    {
        $sql = 'INSERT INTO usuarios (nome, email, senha_hash, telefone, telefone_normalizado, bairro, cidade)
                VALUES (:nome, :email, :senha_hash, :telefone, :telefone_normalizado, :bairro, :cidade)';

        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':senha_hash' => $senhaHash,
            ':telefone' => $telefone,
            ':telefone_normalizado' => $telefoneNormalizado,
            ':bairro' => $bairro,
            ':cidade' => $cidade,
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
        $stmt = db()->prepare('SELECT id, nome, email, email_verificado_em, telefone, telefone_normalizado, bairro, cidade, saldo_pontos, no_show_count, bloqueada_ate FROM usuarios WHERE id = :id AND ativo = 1');
        $stmt->execute([':id' => $id]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public static function atualizarPerfil(int $id, string $nome, ?string $telefone, string $bairro, string $cidade): void
    {
        $telefoneNormalizado = function_exists('normalizar_telefone') ? normalizar_telefone($telefone) : ($telefone ?: null);
        $sql = 'UPDATE usuarios
                SET nome = :nome,
                    telefone = :telefone,
                    telefone_normalizado = :telefone_normalizado,
                    bairro = :bairro,
                    cidade = :cidade,
                    atualizado_em = NOW()
                WHERE id = :id';

        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nome' => $nome,
            ':telefone' => $telefone,
            ':telefone_normalizado' => $telefoneNormalizado,
            ':bairro' => $bairro,
            ':cidade' => $cidade,
        ]);
    }

    public static function confirmarEmail(int $id): void
    {
        $stmt = db()->prepare(
            'UPDATE usuarios
             SET email_verificado_em = COALESCE(email_verificado_em, NOW()), atualizado_em = NOW()
             WHERE id = :id AND ativo = 1'
        );
        $stmt->execute([':id' => $id]);
    }

    public static function resumoPublico(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT
                u.id,
                u.nome,
                u.email_verificado_em,
                (
                    SELECT ROUND(AVG(a.nota), 1)
                    FROM avaliacoes a
                    WHERE a.avaliada_id = u.id
                ) AS avaliacao_media,
                (
                    SELECT COUNT(*)
                    FROM itens i
                    WHERE i.doadora_id = u.id
                      AND i.status = "entregue"
                ) AS itens_doados,
                (
                    SELECT COUNT(*)
                    FROM reservas r
                    WHERE r.receptora_id = u.id
                      AND r.status = "entregue"
                ) AS itens_recebidos,
                (
                    SELECT COUNT(*)
                    FROM denuncias d
                    WHERE d.denunciada_id = u.id
                ) AS denuncias_recebidas,
                u.bairro,
                u.cidade,
                u.no_show_count
             FROM usuarios u
             WHERE u.id = :id AND u.ativo = 1'
        );
        $stmt->execute([':id' => $id]);
        $resumo = $stmt->fetch();

        return $resumo ?: null;
    }
}
