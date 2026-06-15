<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/config/db.php';

$usuario = exigir_login();

$metricas = [
    'entregues' => (int) db()->query('SELECT COUNT(*) FROM itens WHERE status = "entregue"')->fetchColumn(),
    'usuarias' => (int) db()->query('SELECT COUNT(*) FROM usuarios WHERE ativo = 1')->fetchColumn(),
    'pontos_creditados' => (int) db()->query('SELECT COALESCE(SUM(quantidade), 0) FROM transacoes_pontos WHERE tipo = "credito"')->fetchColumn(),
];

$categorias = db()->query(
    'SELECT c.nome, COUNT(*) AS total
     FROM itens i
     JOIN categorias c ON c.id = i.categoria_id
     WHERE i.status = "entregue"
     GROUP BY c.id, c.nome
     ORDER BY total DESC, c.nome ASC
     LIMIT 5'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Painel de impacto</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container">
        <section class="page-header panel">
            <div>
                <h1>Painel de impacto</h1>
                <p class="muted">Uma leitura rápida do que a comunidade já movimentou com as doações.</p>
            </div>
        </section>

        <section class="summary-grid">
            <article class="summary-card">
                <span class="summary-label">Itens entregues</span>
                <strong><?= $metricas['entregues'] ?></strong>
            </article>
            <article class="summary-card">
                <span class="summary-label">Usuárias ativas</span>
                <strong><?= $metricas['usuarias'] ?></strong>
            </article>
            <article class="summary-card">
                <span class="summary-label">Pontos creditados</span>
                <strong><?= $metricas['pontos_creditados'] ?></strong>
            </article>
        </section>

        <section class="panel stack">
            <h2>Categorias com mais entregas</h2>

            <?php if (!$categorias): ?>
                <p class="muted empty-state">As estatísticas aparecem assim que as primeiras entregas forem concluídas.</p>
            <?php endif; ?>

            <?php foreach ($categorias as $categoria): ?>
                <div class="impact-row">
                    <span><?= e($categoria['nome']) ?></span>
                    <strong><?= (int) $categoria['total'] ?> entrega(s)</strong>
                </div>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
