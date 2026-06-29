<?php

declare(strict_types=1);

$root = realpath(__DIR__);
$fallback = $root ? $root . '/assets/img/item-placeholder.svg' : null;

function enviar_fallback_imagem(?string $fallback): void
{
    http_response_code(200);
    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');

    if ($fallback && is_file($fallback)) {
        readfile($fallback);
        return;
    }

    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 620"><rect width="900" height="620" fill="#f4e5ec"/><text x="450" y="320" text-anchor="middle" font-family="Arial" font-size="34" fill="#7a3650">Foto indisponivel</text></svg>';
}

function caminho_imagem_seguro(?string $root, string $src): ?string
{
    if (!$root) {
        return null;
    }

    $src = rawurldecode($src);
    $src = str_replace('\\', '/', $src);
    $src = preg_replace('#/+#', '/', $src) ?? $src;
    $src = ltrim($src, '/');

    while (str_starts_with($src, '../') || str_starts_with($src, './')) {
        $src = str_starts_with($src, '../') ? substr($src, 3) : substr($src, 2);
    }

    foreach (['public_html/', 'src/'] as $prefix) {
        if (str_starts_with($src, $prefix)) {
            $src = substr($src, strlen($prefix));
        }
    }

    if ($src === '' || str_contains($src, '..')) {
        return null;
    }

    if (str_starts_with($src, 'private_uploads/itens/')) {
        $privateRoot = realpath(dirname($root) . '/private_uploads');
        if (!$privateRoot) {
            return null;
        }

        $relative = substr($src, strlen('private_uploads/'));
        $arquivo = realpath($privateRoot . '/' . $relative);
        if (!$arquivo || !str_starts_with($arquivo, $privateRoot) || !is_file($arquivo)) {
            return null;
        }

        return $arquivo;
    }

    $permitidosPublicos = [
        'uploads/itens/',
        'assets/img/',
    ];

    foreach ($permitidosPublicos as $prefixo) {
        if (str_starts_with($src, $prefixo)) {
            $arquivo = realpath($root . '/' . $src);
            if (!$arquivo || !str_starts_with($arquivo, $root) || !is_file($arquivo)) {
                return null;
            }

            return $arquivo;
        }
    }

    return null;
}

$src = (string) ($_GET['src'] ?? '');
$arquivo = caminho_imagem_seguro($root, $src);

if (!$arquivo) {
    enviar_fallback_imagem($fallback);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo) ?: '';
$mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];

if (!in_array($mime, $mimesPermitidos, true)) {
    enviar_fallback_imagem($fallback);
    exit;
}

$mtime = (int) filemtime($arquivo);
$etag = '"' . sha1($arquivo . '|' . $mtime . '|' . filesize($arquivo)) . '"';

header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

$aceitaWebp = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'image/webp');
if ($mime === 'image/webp' && !$aceitaWebp) {
    if (function_exists('imagecreatefromwebp')) {
        $imagem = @imagecreatefromwebp($arquivo);
        if ($imagem) {
            header('Content-Type: image/jpeg');
            imagejpeg($imagem, null, 84);
            imagedestroy($imagem);
            exit;
        }
    }

    enviar_fallback_imagem($fallback);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($arquivo));
readfile($arquivo);
