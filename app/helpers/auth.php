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
        '/usuarios',
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

function favicon_head_tags(): string
{
    return '<link rel="icon" href="' . e(app_url('favicon.svg')) . '" type="image/svg+xml">' . PHP_EOL
        . '    <link rel="shortcut icon" href="' . e(app_url('favicon.svg')) . '" type="image/svg+xml">' . PHP_EOL
        . '    <link rel="apple-touch-icon" href="' . e(app_url('assets/icons/icon.svg')) . '">';
}

function pwa_head_tags(): string
{
    return favicon_head_tags() . PHP_EOL
        . '    <link rel="manifest" href="' . e(app_url('manifest.webmanifest')) . '">' . PHP_EOL
        . '    <meta name="theme-color" content="#b45d7f">' . PHP_EOL
        . '    <meta name="apple-mobile-web-app-capable" content="yes">' . PHP_EOL
        . '    <meta name="apple-mobile-web-app-title" content="ReUse">' . PHP_EOL
        . '    <script src="' . e(app_url('assets/js/pwa.js?v=20260624')) . '" defer></script>';
}

function media_public_path(?string $path): ?string
{
    $path = trim((string) $path);
    if ($path === '') {
        return null;
    }

    $parsedPath = parse_url($path, PHP_URL_PATH);
    $path = $parsedPath !== null && $parsedPath !== false ? $parsedPath : $path;
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#/+#', '/', $path) ?? $path;
    $path = ltrim($path, '/');

    while (str_starts_with($path, '../') || str_starts_with($path, './')) {
        $path = str_starts_with($path, '../') ? substr($path, 3) : substr($path, 2);
    }

    foreach (['public_html/', 'src/'] as $prefix) {
        if (str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }
    }

    if ($path === '' || str_contains($path, '..')) {
        return null;
    }

    return $path;
}

function item_foto_fallback_url(): string
{
    return app_url('assets/img/item-placeholder.svg');
}

function item_foto_url(?string $path): string
{
    $publicPath = media_public_path($path);
    if ($publicPath === null) {
        return item_foto_fallback_url();
    }

    return app_url('imagem.php?src=' . rawurlencode($publicPath));
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

    $ultimaExpiracao = (int) ($_SESSION['ultima_expiracao_reservas'] ?? 0);
    if ($ultimaExpiracao < time() - 60) {
        require_once __DIR__ . '/../repositories/ReservaRepo.php';
        ReservaRepo::expirarVencidas();
        $_SESSION['ultima_expiracao_reservas'] = time();
    }

    return $usuario;
}

function conta_verificada(array $usuario): bool
{
    return !empty($usuario['email_verificado_em']);
}

function nivel_conta(array $usuario): string
{
    if (empty($usuario['email_verificado_em'])) {
        return 'Conta basica';
    }

    $noShow = (int) ($usuario['no_show_count'] ?? 0);
    $avaliacao = isset($usuario['avaliacao_media']) ? (float) $usuario['avaliacao_media'] : null;
    $entregas = (int) ($usuario['itens_doados'] ?? 0);

    if ($entregas >= 3 && $noShow <= 1 && ($avaliacao === null || $avaliacao >= 4.0)) {
        return 'Conta confiavel';
    }

    return 'Conta verificada';
}

function exigir_conta_verificada(array $usuario, string $destino = 'perfil.php'): void
{
    if (conta_verificada($usuario)) {
        return;
    }

    flash_set('error', 'Confirme seu e-mail para publicar itens e fazer reservas no ReUse.');
    header('Location: ' . app_url($destino));
    exit;
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
