<?php

declare(strict_types=1);

function flash_set(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function flash_html(): string
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (!$flash) {
        return '';
    }
    return '<div class="alert '
        . htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($flash['mensagem'], ENT_QUOTES, 'UTF-8')
        . '</div>';
}
