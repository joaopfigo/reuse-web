<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$categorias = ItemRepo::categorias();
$item = ItemRepo::buscarPorId((int) ($_GET['id'] ?? 0));
$erro = '';
$sucesso = '';

if (!$item || (int) $item['doadora_id'] !== (int) $usuario['id']) {
    $erro = 'Item nao encontrado ou sem permissao para editar.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
        'titulo' => trim($_POST['titulo'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
        'condicao' => $_POST['condicao'] ?? '',
        'pontos' => (int) ($_POST['pontos'] ?? 0),
        'bairro' => trim($_POST['bairro'] ?? ''),
    ];

    if (!$dados['titulo'] || !$dados['descricao'] || !$dados['categoria_id'] || !$dados['bairro'] || $dados['pontos'] <= 0) {
        $erro = 'Preencha todos os campos obrigatorios.';
    } else {
        ItemRepo::atualizar((int) $item['id'], (int) $usuario['id'], $dados);
        $item = ItemRepo::buscarPorId((int) $item['id']);
        $sucesso = 'Item atualizado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Editar item</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container">
        <form method="post" class="panel stack">
            <h1>Editar item</h1>
            <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
            <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>

            <?php if ($item && (int) $item['doadora_id'] === (int) $usuario['id']): ?>
                <div class="grid-2">
                    <label>
                        Titulo
                        <input type="text" name="titulo" value="<?= e($item['titulo']) ?>" required>
                    </label>
                    <label>
                        Pontos
                        <input type="number" name="pontos" value="<?= (int) $item['pontos'] ?>" min="1" required>
                    </label>
                </div>
                <label>
                    Descricao
                    <textarea name="descricao" required><?= e($item['descricao']) ?></textarea>
                </label>
                <div class="grid-2">
                    <label>
                        Categoria
                        <select name="categoria_id" required>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['id'] === (int) $item['categoria_id'] ? 'selected' : '' ?>>
                                    <?= e($categoria['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Condicao
                        <select name="condicao" required>
                            <option value="novo" <?= $item['condicao'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                            <option value="seminovo" <?= $item['condicao'] === 'seminovo' ? 'selected' : '' ?>>Seminovo</option>
                            <option value="usado_bom" <?= $item['condicao'] === 'usado_bom' ? 'selected' : '' ?>>Usado em bom estado</option>
                            <option value="usado_regular" <?= $item['condicao'] === 'usado_regular' ? 'selected' : '' ?>>Usado regular</option>
                        </select>
                    </label>
                </div>
                <label>
                    Bairro aproximado
                    <input type="text" name="bairro" value="<?= e($item['bairro']) ?>" required>
                </label>
                <button class="btn primary" type="submit">Salvar alteracoes</button>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>
