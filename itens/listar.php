<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$filtros = [
    'q' => trim($_GET['q'] ?? ''),
    'categoria_id' => trim($_GET['categoria_id'] ?? ''),
    'condicao' => trim($_GET['condicao'] ?? ''),
];
$erroSistema = '';
$usuario = null;
$categorias = [];
$itens = [];

try {
    $usuario = exigir_login();
    $categorias = ItemRepo::categorias();
    $itens = ItemRepo::listar($filtros);
} catch (Throwable $erroAplicacao) {
    $erroSistema = $erroAplicacao->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Itens</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php if ($usuario): ?>
        <?php render_topbar($usuario); ?>
    <?php endif; ?>

    <main class="container layout">
        <aside class="panel">
            <h1>Itens</h1>
            <p class="muted">Busque e filtre itens disponiveis para reuso.</p>

            <?= flash_html() ?>

            <?php if ($erroSistema): ?>
                <div class="alert error"><?= e($erroSistema) ?></div>
            <?php endif; ?>

            <form method="get" class="stack">
                <label>
                    Palavra-chave
                    <input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Ex.: livro, bolsa, blusa">
                </label>

                <label>
                    Categoria
                    <select name="categoria_id">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria['id'] ?>" <?= (string) $categoria['id'] === $filtros['categoria_id'] ? 'selected' : '' ?>>
                                <?= e($categoria['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Condicao
                    <select name="condicao">
                        <option value="">Todas</option>
                        <option value="novo" <?= $filtros['condicao'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                        <option value="seminovo" <?= $filtros['condicao'] === 'seminovo' ? 'selected' : '' ?>>Seminovo</option>
                        <option value="usado_bom" <?= $filtros['condicao'] === 'usado_bom' ? 'selected' : '' ?>>Usado em bom estado</option>
                        <option value="usado_regular" <?= $filtros['condicao'] === 'usado_regular' ? 'selected' : '' ?>>Usado regular</option>
                    </select>
                </label>

                <button class="btn primary" type="submit">Buscar</button>
                <a class="btn secondary" href="listar.php">Limpar</a>
            </form>
        </aside>

        <section class="items-grid">
            <?php if (!$itens): ?>
                <div class="panel">Nenhum item encontrado.</div>
            <?php endif; ?>

            <?php foreach ($itens as $item): ?>
                <article class="item-card">
                    <div class="item-photo">
                        <?php if ($item['foto']): ?>
                            <img src="../<?= e($item['foto']) ?>" alt="Foto de <?= e($item['titulo']) ?>">
                        <?php else: ?>
                            Sem foto
                        <?php endif; ?>
                    </div>
                    <div class="item-body">
                        <h2><?= e($item['titulo']) ?></h2>
                        <p class="meta"><?= e($item['bairro']) ?> | Doadora: <?= e($item['doadora']) ?></p>
                        <div class="badge-row">
                            <span class="badge"><?= e($item['categoria']) ?></span>
                            <span class="badge"><?= e($item['condicao']) ?></span>
                            <span class="badge"><?= (int) $item['pontos'] ?> pontos</span>
                        </div>
                        <a href="detalhe.php?id=<?= (int) $item['id'] ?>">Ver detalhes</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
