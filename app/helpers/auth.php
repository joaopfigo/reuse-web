<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/../repositories/UsuarioRepo.php';

function string_ends_with(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    if (strlen($needle) > strlen($haystack)) {
        return false;
    }

    return substr($haystack, -strlen($needle)) === $needle;
}

function app_url(string $path = ''): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = $scriptDir === '.' ? '' : rtrim($scriptDir, '/');

    $subpastasDaAplicacao = [
        '/itens',
        '/reservas',
        '/pontos',
        '/notificacoes',
        '/denuncias',
        '/avaliacoes',
        '/impacto',
    ];

    foreach ($subpastasDaAplicacao as $subpasta) {
        if (string_ends_with($scriptDir, $subpasta)) {
            $scriptDir = substr($scriptDir, 0, -strlen($subpasta));
            break;
        }
    }

    if ($scriptDir === '/' || $scriptDir === false) {
        $scriptDir = '';
    }

    $suffix = ltrim($path, '/');

    return $suffix === '' ? $scriptDir : $scriptDir . '/' . $suffix;
}

function usuario_logado(): ?array
{
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    return UsuarioRepo::buscarPorId((int) $_SESSION['usuario_id']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function validar_csrf_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sessão expirada. Atualize a página e tente novamente.');
    }
}

function exigir_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Método não permitido.');
    }
}

function exigir_login(): array
{
    $usuario = usuario_logado();

    if (!$usuario) {
        header('Location: ' . app_url('login.php'));
        exit;
    }

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

function status_label(string $status): string
{
    $mapa = [
        'disponivel' => 'Disponível',
        'reservado' => 'Reservado',
        'entregue' => 'Entregue',
        'pausado' => 'Pausado',
        'cancelado' => 'Cancelado',
        'cancelada' => 'Cancelada',
        'pendente' => 'Pendente',
        'aceita' => 'Aceita',
        'expirada' => 'Expirada',
        'no_show' => 'Não compareceu',
        'novo' => 'Novo',
        'seminovo' => 'Seminovo',
        'usado_bom' => 'Usado em bom estado',
        'usado_regular' => 'Usado regular',
        'credito' => 'Crédito',
        'debito' => 'Débito',
        'ajuste' => 'Ajuste',
    ];

    return $mapa[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function status_badge_class(string $status): string
{
    return 'badge status-' . preg_replace('/[^a-z_]+/', '', strtolower($status));
}

function formatar_data_hora(?string $valor): string
{
    if (!$valor) {
        return 'Não informado';
    }

    $timestamp = strtotime($valor);
    if ($timestamp === false) {
        return $valor;
    }

    return date('d/m/Y', $timestamp) . ' às ' . date('H:i', $timestamp);
}
