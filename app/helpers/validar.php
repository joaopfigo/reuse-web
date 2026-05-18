<?php

declare(strict_types=1);

function campo_obrigatorio(array $dados, string $campo): bool
{
    return isset($dados[$campo]) && trim((string) $dados[$campo]) !== '';
}

function validar_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function salvar_upload_item(array $arquivo): ?string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erro ao enviar imagem.');
    }

    if (($arquivo['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no maximo 2MB.');
    }

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $mime = mime_content_type($arquivo['tmp_name']);
    if (!isset($permitidos[$mime])) {
        throw new RuntimeException('Use apenas imagens JPG, PNG ou WEBP.');
    }

    $dir = __DIR__ . '/../../uploads/itens';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $nome = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];
    $destino = $dir . '/' . $nome;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Nao foi possivel salvar a imagem.');
    }

    return 'uploads/itens/' . $nome;
}
