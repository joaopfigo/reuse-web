<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/UsuarioRepo.php';
require_once __DIR__ . '/../repositories/EmailConfirmacaoRepo.php';
require_once __DIR__ . '/EmailService.php';

class AuthService
{
    public static function cadastrar(string $nome, string $email, string $senha, ?string $telefone, ?string $telefoneNormalizado, string $bairro, string $cidade): array
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $email = strtolower($email);

        $usuarioId = UsuarioRepo::criar($nome, $email, $senhaHash, $telefone, $telefoneNormalizado, $bairro, $cidade);
        $token = EmailConfirmacaoRepo::criar($usuarioId);
        $link = self::linkConfirmacao($token);
        $emailEnviado = EmailService::enviarConfirmacao($email, $nome, $link);

        return [
            'usuario_id' => $usuarioId,
            'email_enviado' => $emailEnviado,
        ];
    }

    public static function autenticar(string $email, string $senha): ?array
    {
        $usuario = UsuarioRepo::buscarPorEmail(strtolower($email));

        if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
            return null;
        }

        return $usuario;
    }

    public static function reenviarConfirmacao(array $usuario): bool
    {
        if (!empty($usuario['email_verificado_em'])) {
            return true;
        }

        $token = EmailConfirmacaoRepo::criar((int) $usuario['id']);
        $link = self::linkConfirmacao($token);

        return EmailService::enviarConfirmacao((string) $usuario['email'], (string) $usuario['nome'], $link);
    }

    private static function linkConfirmacao(string $token): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . app_url('confirmar-email.php?token=' . urlencode($token));
    }
}
