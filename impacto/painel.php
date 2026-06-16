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
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=20260615">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container dash-page">
        <section class="hero-card">
            <div class="hero-main">
                <span class="hero-tag">Impacto coletivo</span>
                <h1>Painel de impacto</h1>
                <p>Uma leitura clara do que a comunidade já movimentou em reaproveitamento, circulação de pontos e participação ativa.</p>
            </div>

            <div class="hero-side">
                <div class="hero-pill">
                    <span>Rede em movimento</span>
                    <strong><?= $metricas['usuarias'] ?> participantes ativas</strong>
                </div>
                <div class="hero-pill">
                    <span>Força da comunidade</span>
                    <strong><?= $metricas['entregues'] ?> entregas concluídas</strong>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <article class="stat-card featured">
                <span class="stat-label">Itens entregues</span>
                <strong class="stat-value"><?= $metricas['entregues'] ?></strong>
                <span class="stat-unit">itens concluídos</span>
                <p class="stat-note">Cada entrega confirmada representa um item que ganhou novo uso dentro da rede.</p>
            </article>

            <article class="stat-card">
                <span class="stat-label">Usuárias ativas</span>
                <strong class="stat-value"><?= $metricas['usuarias'] ?></strong>
                <span class="stat-unit">contas participantes</span>
                <p class="stat-note">A comunidade cresce quando mais pessoas conseguem doar, reservar e retirar com segurança.</p>
            </article>

            <article class="stat-card">
                <span class="stat-label">Pontos creditados</span>
                <strong class="stat-value"><?= $metricas['pontos_creditados'] ?></strong>
                <span class="stat-unit">pontos movimentados</span>
                <p class="stat-note">Essa é a soma de créditos gerados pelas entregas já finalizadas no sistema.</p>
            </article>
        </section>

        <section class="dash-columns">
            <section class="dash-panel">
                <div class="section-intro">
                    <span class="section-kicker">Leitura por categoria</span>
                    <h2 class="section-title">Categorias com mais entregas</h2>
                    <p class="section-copy">Acompanhe quais frentes estão circulando melhor dentro da comunidade.</p>
                </div>

                <?php if (!$categorias): ?>
                    <div class="soft-alert">
                        <p class="section-copy">As estatísticas aparecem assim que as primeiras entregas forem concluídas.</p>
                    </div>
                <?php else: ?>
                    <div class="impact-list clean">
                        <?php foreach ($categorias as $categoria): ?>
                            <div class="impact-item">
                                <div class="impact-item-copy">
                                    <strong><?= e($categoria['nome']) ?></strong>
                                    <p>Categoria mais movimentada no momento.</p>
                                </div>
                                <span class="impact-chip"><?= (int) $categoria['total'] ?> entrega(s)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="dash-stack">
                <section class="dash-panel">
                    <div class="section-intro">
                        <span class="section-kicker">O que observar</span>
                        <h2 class="section-title">Como ler esse painel</h2>
                    </div>

                    <ul class="insight-list clean">
                        <li>Entregas concluídas mostram o volume real de reaproveitamento.</li>
                        <li>Pontos creditados revelam a atividade econômica interna da comunidade.</li>
                        <li>Categorias ajudam a identificar onde a rede está mais forte.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
