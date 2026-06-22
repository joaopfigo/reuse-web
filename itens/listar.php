<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$filtros = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'categoria_id' => trim((string) ($_GET['categoria_id'] ?? '')),
    'condicao' => trim((string) ($_GET['condicao'] ?? '')),
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

$temFiltro = $filtros['q'] !== '' || $filtros['categoria_id'] !== '' || $filtros['condicao'] !== '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Itens</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260615">
</head>
<body>
    <?php if ($usuario): ?>
        <?php render_topbar($usuario); ?>
    <?php endif; ?>

    <main class="container ops-page">
        <section class="ops-hero">
            <div class="ops-hero-main">
                <span class="ops-kicker">Feed de itens</span>
                <h1 class="ops-title">Encontre doações disponíveis com mais contexto e organização.</h1>
                <p class="ops-copy">Use filtros por categoria, condição e palavra-chave para localizar itens com mais rapidez e acompanhar o que está disponível na comunidade.</p>
            </div>
            <div class="ops-hero-side">
                <div class="ops-side-card">
                    <span>Resultados atuais</span>
                    <strong><?= count($itens) ?> item(ns) disponíveis</strong>
                </div>
            </div>
        </section>

        <section class="surface-grid">
            <aside class="surface-panel">
                <div class="section-header">
                    <h2>Filtros</h2>
                    <p>Refine a busca para encontrar mais rápido o que faz sentido para você.</p>
                </div>

                <?= flash_html() ?>

                <?php if ($erroSistema): ?>
                    <div class="alert error"><?= e($erroSistema) ?></div>
                <?php endif; ?>

                <form method="get" class="section-stack">
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
                        Condição
                        <select name="condicao">
                            <option value="">Todas</option>
                            <option value="novo" <?= $filtros['condicao'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                            <option value="seminovo" <?= $filtros['condicao'] === 'seminovo' ? 'selected' : '' ?>>Seminovo</option>
                            <option value="usado_bom" <?= $filtros['condicao'] === 'usado_bom' ? 'selected' : '' ?>>Usado em bom estado</option>
                            <option value="usado_regular" <?= $filtros['condicao'] === 'usado_regular' ? 'selected' : '' ?>>Usado regular</option>
                        </select>
                    </label>

                    <div class="list-card-actions">
                        <button class="btn primary" type="submit">Buscar</button>
                        <a class="btn secondary" href="listar.php">Limpar</a>
                    </div>
                </form>
            </aside>

            <section class="section-stack">
                <?php if ($temFiltro): ?>
                    <div class="soft-note">
                        <strong><?= count($itens) ?></strong> resultado(s)
                        <?php if ($filtros['q'] !== ''): ?>
                            para <strong>"<?= e($filtros['q']) ?>"</strong>
                        <?php endif; ?>.
                    </div>
                <?php endif; ?>

                <?php if (!$itens): ?>
                    <div class="empty-card">
                        <?php if ($filtros['q'] !== ''): ?>
                            Nenhum item encontrado para <strong>"<?= e($filtros['q']) ?>"</strong>. Tente outros termos ou limpe os filtros.
                        <?php else: ?>
                            Nenhum item disponível no momento.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <section class="items-grid">
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
                                <p class="meta"><?= e($item['bairro']) ?> · Doador(a): <?= e($item['doadora']) ?></p>
                                <div class="badge-row">
                                    <span class="badge"><?= e($item['categoria']) ?></span>
                                    <span class="badge"><?= e(status_label((string) $item['condicao'])) ?></span>
                                    <span class="badge"><?= (int) $item['pontos'] ?> pontos</span>
                                </div>
                                <a href="detalhe.php?id=<?= (int) $item['id'] ?>">Ver detalhes</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            </section>
        </section>
    </main>
</body>
</html>
