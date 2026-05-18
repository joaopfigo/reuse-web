<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/UsuarioRepo.php';

class AuthService
{
    public static function cadastrar(string $nome, string $email, string $senha, ?string $telefone, string $bairro): int
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        return UsuarioRepo::criar($nome, strtolower($email), $senhaHash, $telefone, $bairro);
    }

    public static function autenticar(string $email, string $senha): ?array
    {
        $usuario = UsuarioRepo::buscarPorEmail(strtolower($email));

        if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
            return null;
        }

        return $usuario;
    }
}
