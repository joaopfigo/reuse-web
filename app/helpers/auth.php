<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/../repositories/UsuarioRepo.php';

function app_url(string $path = ''): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $knownSuffixes = [
        '/index.php',
        '/login.php',
        '/logout.php',
        '/cadastro.php',
        '/perfil.php',
        '/itens/listar.php',
        '/itens/detalhe.php',
        '/itens/criar.php',
        '/itens/editar.php',
        '/reservas/reservar.php',
        '/reservas/minhas.php',
        '/reservas/confirmar.php',
        '/pontos/carteira.php',
    ];

    $base = '';
    foreach ($knownSuffixes as $suffix) {
        if (str_ends_with($scriptName, $suffix)) {
            $base = substr($scriptName, 0, -strlen($suffix));
            break;
        }
    }

    $base = rtrim($base, '/');
    $suffix = ltrim($path, '/');

    return $suffix === '' ? $base : $base . '/' . $suffix;
}

function usuario_logado(): ?array
{
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    return UsuarioRepo::buscarPorId((int) $_SESSION['usuario_id']);
}

function exigir_login(): array
{
    $usuario = usuario_logado();

    if (!$usuario) {
        header('Location: ' . app_url('login.php'));
        exit;
    }

    // RF11: expirar reservas vencidas — uma vez por sessão
    if (empty($_SESSION['expirou_reservas'])) {
        require_once __DIR__ . '/../repositories/ReservaRepo.php';
        ReservaRepo::expirarVencidas();
        $_SESSION['expirou_reservas'] = true;
    }

    return $usuario;
}

function redirecionar_se_logado(): void
{
    if (usuario_logado()) {
        header('Location: ' . app_url('itens/listar.php'));
        exit;
    }
}

function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
