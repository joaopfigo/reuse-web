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

function normalizar_telefone(?string $telefone): ?string
{
    if ($telefone === null) {
        return null;
    }

    $digitos = preg_replace('/\D+/', '', $telefone) ?? '';

    return $digitos !== '' ? $digitos : null;
}

function normalizar_texto_item(string $texto): string
{
    $texto = function_exists('mb_strtolower')
        ? mb_strtolower(trim($texto), 'UTF-8')
        : strtolower(trim($texto));
    $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($transliterado !== false) {
        $texto = $transliterado;
    }

    $texto = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $texto) ?? $texto;
    $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

    return trim($texto);
}

function similaridade_texto_item(string $a, string $b): float
{
    $a = normalizar_texto_item($a);
    $b = normalizar_texto_item($b);

    if ($a === '' && $b === '') {
        return 1.0;
    }

    if ($a === '' || $b === '') {
        return 0.0;
    }

    $maior = max(strlen($a), strlen($b));
    if ($maior === 0) {
        return 1.0;
    }

    return 1 - (levenshtein($a, $b) / $maior);
}

function salvar_upload_item(array $arquivo): ?array
{
    [$dir, $pathPublico] = diretorio_upload_item();

    return salvar_upload_imagem($arquivo, $dir, $pathPublico, 2 * 1024 * 1024);
}

function diretorio_upload_item(): array
{
    $privateBase = dirname(__DIR__, 3) . '/private_uploads';
    $privateDir = $privateBase . '/itens';

    if ((is_dir($privateDir) || @mkdir($privateDir, 0755, true)) && is_writable($privateDir)) {
        return [$privateDir, 'private_uploads/itens'];
    }

    return [__DIR__ . '/../../uploads/itens', 'uploads/itens'];
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

function remover_uploads_item(array $fotos): void
{
    foreach ($fotos as $foto) {
        $caminho = is_array($foto) ? ($foto['caminho'] ?? '') : (string) $foto;
        if ($caminho === '') {
            continue;
        }

        $relativo = ltrim(str_replace('\\', '/', $caminho), '/');
        $absoluto = false;
        foreach ([__DIR__ . '/../../' . $relativo, dirname(__DIR__, 3) . '/' . $relativo] as $candidato) {
            $resolvido = realpath($candidato);
            if ($resolvido) {
                $absoluto = $resolvido;
                break;
            }
        }

        if (!$absoluto) {
            continue;
        }

        $bases = array_filter([
            realpath(__DIR__ . '/../../uploads/itens'),
            realpath(dirname(__DIR__, 3) . '/private_uploads/itens'),
        ]);

        $permitido = false;
        foreach ($bases as $base) {
            if (str_starts_with($absoluto, $base)) {
                $permitido = true;
                break;
            }
        }

        if ($absoluto && $permitido && is_file($absoluto)) {
            unlink($absoluto);
        }
    }
}

function salvar_upload_denuncia(array $arquivo): ?string
{
    $upload = salvar_upload_imagem($arquivo, __DIR__ . '/../../uploads/denuncias', 'uploads/denuncias', 4 * 1024 * 1024);

    return $upload['caminho'] ?? null;
}

function salvar_upload_imagem(array $arquivo, string $dir, string $pathPublico, int $limiteBytes): ?array
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

    $nomeBase = bin2hex(random_bytes(16));
    $mimeFinal = $mime;
    $salvouOtimizada = false;

    if (extension_loaded('gd')) {
        $destinoOtimizado = $dir . '/' . $nomeBase . '.jpg';
        $salvouOtimizada = salvar_imagem_jpeg_otimizada($arquivo['tmp_name'], $destinoOtimizado, $mime);
        if ($salvouOtimizada) {
            $destino = $destinoOtimizado;
            $mimeFinal = 'image/jpeg';
        }
    }

    if (!$salvouOtimizada) {
        $destino = $dir . '/' . $nomeBase . '.' . $permitidos[$mime];
        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            throw new RuntimeException('Nao foi possivel salvar a imagem.');
        }
    }

    return [
        'caminho' => rtrim($pathPublico, '/') . '/' . basename($destino),
        'hash_perceptual' => hash_perceptual_imagem($destino, $mimeFinal),
    ];
}

function salvar_imagem_jpeg_otimizada(string $origem, string $destino, string $mime): bool
{
    $imagem = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($origem),
        'image/png' => @imagecreatefrompng($origem),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($origem) : false,
        default => false,
    };

    if (!$imagem) {
        return false;
    }

    if ($mime === 'image/jpeg') {
        $imagem = corrigir_orientacao_jpeg($imagem, $origem);
    }

    $larguraOriginal = imagesx($imagem);
    $alturaOriginal = imagesy($imagem);
    $maiorLado = max($larguraOriginal, $alturaOriginal);
    $limiteLado = 1600;
    $escala = $maiorLado > $limiteLado ? ($limiteLado / $maiorLado) : 1;
    $largura = max(1, (int) round($larguraOriginal * $escala));
    $altura = max(1, (int) round($alturaOriginal * $escala));

    $saida = imagecreatetruecolor($largura, $altura);
    $fundo = imagecolorallocate($saida, 255, 255, 255);
    imagefill($saida, 0, 0, $fundo);
    imagecopyresampled($saida, $imagem, 0, 0, 0, 0, $largura, $altura, $larguraOriginal, $alturaOriginal);
    imageinterlace($saida, true);

    $ok = imagejpeg($saida, $destino, 84);

    imagedestroy($saida);
    imagedestroy($imagem);

    return $ok;
}

function corrigir_orientacao_jpeg($imagem, string $arquivo)
{
    if (!function_exists('exif_read_data')) {
        return $imagem;
    }

    $exif = @exif_read_data($arquivo);
    $orientacao = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
    $rotacionada = match ($orientacao) {
        3 => imagerotate($imagem, 180, 0),
        6 => imagerotate($imagem, -90, 0),
        8 => imagerotate($imagem, 90, 0),
        default => false,
    };

    if (!$rotacionada) {
        return $imagem;
    }

    imagedestroy($imagem);
    return $rotacionada;
}

function hash_perceptual_imagem(string $arquivo, ?string $mime = null): ?string
{
    if (!extension_loaded('gd')) {
        return null;
    }

    $mime ??= (new finfo(FILEINFO_MIME_TYPE))->file($arquivo) ?: null;

    $imagem = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($arquivo),
        'image/png' => imagecreatefrompng($arquivo),
        'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($arquivo) : false,
        default => false,
    };

    if (!$imagem) {
        return null;
    }

    $largura = 9;
    $altura = 8;
    $reduzida = imagecreatetruecolor($largura, $altura);
    imagecopyresampled($reduzida, $imagem, 0, 0, 0, 0, $largura, $altura, imagesx($imagem), imagesy($imagem));

    $bits = '';
    for ($y = 0; $y < $altura; $y++) {
        for ($x = 0; $x < $largura - 1; $x++) {
            $pixelAtual = imagecolorat($reduzida, $x, $y);
            $pixelProximo = imagecolorat($reduzida, $x + 1, $y);
            $brilhoAtual = brilho_pixel($pixelAtual);
            $brilhoProximo = brilho_pixel($pixelProximo);
            $bits .= $brilhoAtual > $brilhoProximo ? '1' : '0';
        }
    }

    imagedestroy($reduzida);
    imagedestroy($imagem);

    $hex = '';
    foreach (str_split($bits, 4) as $nibble) {
        $hex .= dechex(bindec($nibble));
    }

    return str_pad($hex, 16, '0', STR_PAD_LEFT);
}

function brilho_pixel(int $pixel): int
{
    $r = ($pixel >> 16) & 0xFF;
    $g = ($pixel >> 8) & 0xFF;
    $b = $pixel & 0xFF;

    return (int) round(($r * 0.299) + ($g * 0.587) + ($b * 0.114));
}

function distancia_hamming_hash(?string $a, ?string $b): int
{
    if (!$a || !$b || strlen($a) !== strlen($b)) {
        return PHP_INT_MAX;
    }

    $distancia = 0;
    for ($i = 0, $len = strlen($a); $i < $len; $i++) {
        $xor = hexdec($a[$i]) ^ hexdec($b[$i]);
        $distancia += substr_count(decbin($xor), '1');
    }

    return $distancia;
}
