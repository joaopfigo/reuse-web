<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/helpers/validar.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$categorias = ItemRepo::categorias();
$erro = '';
$sucesso = '';

$dados = [
    'categoria_id' => (int) ($_POST['categoria_id'] ?? ($categorias[0]['id'] ?? 0)),
    'titulo' => trim((string) ($_POST['titulo'] ?? '')),
    'descricao' => trim((string) ($_POST['descricao'] ?? '')),
    'condicao' => (string) ($_POST['condicao'] ?? 'novo'),
    'pontos' => (int) ($_POST['pontos'] ?? 5),
    'bairro' => trim((string) ($_POST['bairro'] ?? $usuario['bairro'])),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();

    try {
        $dados['doadora_id'] = (int) $usuario['id'];

        if (!$dados['titulo'] || !$dados['descricao'] || !$dados['categoria_id'] || !$dados['bairro'] || $dados['pontos'] <= 0) {
            throw new RuntimeException('Preencha todos os campos obrigatórios.');
        }

        if (strlen($dados['descricao']) < 20) {
            throw new RuntimeException('Descreva melhor o item para ajudar a comunidade a entender o que está sendo doado.');
        }

        $fotoPath = salvar_upload_item($_FILES['foto'] ?? []);
        ItemRepo::criar($dados, $fotoPath);
        $sucesso = 'Item cadastrado com sucesso.';
        $dados['titulo'] = '';
        $dados['descricao'] = '';
        $dados['condicao'] = 'novo';
        $dados['pontos'] = 5;
        $dados['bairro'] = $usuario['bairro'];
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Cadastrar item</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container">
        <form method="post" enctype="multipart/form-data" class="panel stack">
            <h1>Cadastrar item</h1>
            <p class="muted">Informe apenas a região ou bairro aproximado, nunca o endereço completo.</p>
            <?= csrf_input() ?>

            <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
            <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>

            <div class="grid-2">
                <label>
                    Título
                    <input type="text" name="titulo" value="<?= e($dados['titulo']) ?>" required>
                </label>

                <label>
                    Pontos
                    <input type="number" name="pontos" min="1" value="<?= (int) $dados['pontos'] ?>" required>
                </label>
            </div>

            <label>
                Descrição
                <textarea name="descricao" required><?= e($dados['descricao']) ?></textarea>
                <small class="muted">Conte o estado do item, tamanho, marca ou qualquer detalhe útil para a retirada.</small>
            </label>

            <div class="grid-2">
                <label>
                    Categoria
                    <select name="categoria_id" required>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['id'] === (int) $dados['categoria_id'] ? 'selected' : '' ?>>
                                <?= e($categoria['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Condição
                    <select name="condicao" required>
                        <option value="novo" <?= $dados['condicao'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                        <option value="seminovo" <?= $dados['condicao'] === 'seminovo' ? 'selected' : '' ?>>Seminovo</option>
                        <option value="usado_bom" <?= $dados['condicao'] === 'usado_bom' ? 'selected' : '' ?>>Usado em bom estado</option>
                        <option value="usado_regular" <?= $dados['condicao'] === 'usado_regular' ? 'selected' : '' ?>>Usado regular</option>
                    </select>
                </label>
            </div>

            <label>
                Bairro aproximado
                <input type="text" name="bairro" value="<?= e($dados['bairro']) ?>" required>
            </label>

            <label>
                Foto do item (JPG, PNG ou WEBP até 2 MB)
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" data-image-preview-input>
            </label>
            <div class="image-preview" data-image-preview hidden></div>

            <button class="btn primary" type="submit">Publicar</button>
        </form>
    </main>
</body>
</html>
