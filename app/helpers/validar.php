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
    return salvar_upload_imagem($arquivo, __DIR__ . '/../../uploads/itens', 'uploads/itens', 2 * 1024 * 1024);
}

function salvar_uploads_item(array $arquivos): array
{
    if (!isset($arquivos['name'])) {
        return [];
    }

    if (!is_array($arquivos['name'])) {
        $foto = salvar_upload_item($arquivos);
        return $foto ? [$foto] : [];
    }

    $fotos = [];
    $total = count($arquivos['name']);

    for ($i = 0; $i < $total; $i++) {
        $arquivo = [
            'name' => $arquivos['name'][$i] ?? '',
            'type' => $arquivos['type'][$i] ?? '',
            'tmp_name' => $arquivos['tmp_name'][$i] ?? '',
            'error' => $arquivos['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $arquivos['size'][$i] ?? 0,
        ];

        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (count($fotos) >= 5) {
            throw new RuntimeException('Envie no máximo 5 fotos por item.');
        }

        $foto = salvar_upload_item($arquivo);
        if ($foto) {
            $fotos[] = $foto;
        }
    }

    return $fotos;
}

function salvar_upload_denuncia(array $arquivo): ?string
{
    return salvar_upload_imagem($arquivo, __DIR__ . '/../../uploads/denuncias', 'uploads/denuncias', 4 * 1024 * 1024);
}

function salvar_upload_imagem(array $arquivo, string $dir, string $pathPublico, int $limiteBytes): ?string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erro ao enviar imagem.');
    }

    if (($arquivo['size'] ?? 0) > $limiteBytes) {
        throw new RuntimeException('A imagem ultrapassa o tamanho máximo permitido.');
    }

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($arquivo['tmp_name']);
    if (!isset($permitidos[$mime])) {
        throw new RuntimeException('Use apenas imagens JPG, PNG ou WEBP.');
    }

    $dimensoes = getimagesize($arquivo['tmp_name']);
    if ($dimensoes === false) {
        throw new RuntimeException('O arquivo enviado não é uma imagem válida.');
    }

    [$largura, $altura] = $dimensoes;
    if ($largura < 80 || $altura < 80 || $largura > 6000 || $altura > 6000) {
        throw new RuntimeException('A imagem precisa ter dimensões válidas.');
    }

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $nome = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];
    $destino = $dir . '/' . $nome;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Nao foi possivel salvar a imagem.');
    }

    return rtrim($pathPublico, '/') . '/' . $nome;
}
