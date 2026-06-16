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

    <main class="container dashboard-shell">
        <section class="dashboard-hero dashboard-hero-impact">
            <div class="dashboard-hero-copy">
                <span class="eyebrow">Impacto coletivo</span>
                <h1>Painel de impacto</h1>
                <p>Uma leitura clara do que a comunidade já movimentou em reaproveitamento, circulação de pontos e participação ativa.</p>
            </div>

            <div class="hero-aside">
                <div class="hero-chip">
                    <span class="hero-chip-label">Rede em movimento</span>
                    <strong><?= $metricas['usuarias'] ?> participantes ativas</strong>
                </div>
                <div class="hero-chip">
                    <span class="hero-chip-label">Força da comunidade</span>
                    <strong><?= $metricas['entregues'] ?> entregas concluídas</strong>
                </div>
            </div>
        </section>

        <section class="metric-grid metric-grid-impact">
            <article class="metric-card metric-card-accent">
                <span class="metric-kicker">Itens entregues</span>
                <strong class="metric-value"><?= $metricas['entregues'] ?></strong>
                <span class="metric-unit">itens concluídos</span>
                <p class="metric-note">Cada entrega confirmada representa um item que ganhou novo uso dentro da rede.</p>
            </article>

            <article class="metric-card">
                <span class="metric-kicker">Usuárias ativas</span>
                <strong class="metric-value"><?= $metricas['usuarias'] ?></strong>
                <span class="metric-unit">contas participantes</span>
                <p class="metric-note">A comunidade cresce quando mais pessoas conseguem doar, reservar e retirar com segurança.</p>
            </article>

            <article class="metric-card">
                <span class="metric-kicker">Pontos creditados</span>
                <strong class="metric-value"><?= $metricas['pontos_creditados'] ?></strong>
                <span class="metric-unit">pontos movimentados</span>
                <p class="metric-note">Essa é a soma de créditos gerados pelas entregas já finalizadas no sistema.</p>
            </article>
        </section>

        <section class="impact-layout">
            <section class="panel impact-panel stack">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Leitura por categoria</span>
                        <h2>Categorias com mais entregas</h2>
                    </div>
                </div>

                <?php if (!$categorias): ?>
                    <p class="muted empty-state">As estatísticas aparecem assim que as primeiras entregas forem concluídas.</p>
                <?php endif; ?>

                <div class="impact-list">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="impact-row">
                            <div>
                                <strong><?= e($categoria['nome']) ?></strong>
                                <p class="muted">Categoria mais movimentada no momento.</p>
                            </div>
                            <span class="impact-value"><?= (int) $categoria['total'] ?> entrega(s)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside class="panel stack impact-side-panel">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">O que observar</span>
                        <h2>Como ler esse painel</h2>
                    </div>
                </div>

                <ul class="insight-list">
                    <li>Entregas concluídas mostram o volume real de reaproveitamento.</li>
                    <li>Pontos creditados revelam a atividade econômica interna da comunidade.</li>
                    <li>Categorias ajudam a identificar onde a rede está mais forte.</li>
                </ul>
            </aside>
        </section>
    </main>
</body>
</html>
