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
    $erro = 'Item não encontrado ou sem permissão para editar.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();

    $dados = [
        'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
        'titulo' => trim((string) ($_POST['titulo'] ?? '')),
        'descricao' => trim((string) ($_POST['descricao'] ?? '')),
        'condicao' => (string) ($_POST['condicao'] ?? ''),
        'pontos' => (int) ($_POST['pontos'] ?? 0),
        'bairro' => trim((string) ($_POST['bairro'] ?? '')),
    ];

    if (!$dados['titulo'] || !$dados['descricao'] || !$dados['categoria_id'] || !$dados['bairro'] || $dados['pontos'] <= 0) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (strlen($dados['descricao']) < 20) {
        $erro = 'A descrição precisa ter pelo menos 20 caracteres.';
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
            <?= csrf_input() ?>
            <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
            <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>

            <?php if ($item && (int) $item['doadora_id'] === (int) $usuario['id']): ?>
                <div class="grid-2">
                    <label>
                        Título
                        <input type="text" name="titulo" value="<?= e($item['titulo']) ?>" required>
                    </label>
                    <label>
                        Pontos
                        <input type="number" name="pontos" value="<?= (int) $item['pontos'] ?>" min="1" required>
                    </label>
                </div>
                <label>
                    Descrição
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
                        Condição
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
                <div class="action-row">
                    <button class="btn primary" type="submit">Salvar alterações</button>
                    <a class="btn" href="detalhe.php?id=<?= (int) $item['id'] ?>">Voltar</a>
                </div>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>
