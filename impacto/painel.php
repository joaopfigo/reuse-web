<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/config/db.php';

$usuario = exigir_login();
$periodo = (string) ($_GET['periodo'] ?? 'todos');
$periodosValidos = ['30', '90', 'todos'];
$periodo = in_array($periodo, $periodosValidos, true) ? $periodo : 'todos';
$dataInicio = match ($periodo) {
    '30' => date('Y-m-d H:i:s', strtotime('-30 days')),
    '90' => date('Y-m-d H:i:s', strtotime('-90 days')),
    default => null,
};
$wherePeriodoItens = $dataInicio ? ' AND atualizado_em >= :data_inicio' : '';
$wherePeriodoPontos = $dataInicio ? ' AND criado_em >= :data_inicio' : '';
$paramsPeriodo = $dataInicio ? [':data_inicio' => $dataInicio] : [];

$stmtEntregues = db()->prepare('SELECT COUNT(*) FROM itens WHERE status = "entregue"' . $wherePeriodoItens);
$stmtEntregues->execute($paramsPeriodo);

$stmtPontos = db()->prepare('SELECT COALESCE(SUM(quantidade), 0) FROM transacoes_pontos WHERE tipo = "credito"' . $wherePeriodoPontos);
$stmtPontos->execute($paramsPeriodo);

$metricas = [
    'entregues' => (int) $stmtEntregues->fetchColumn(),
    'usuarias' => (int) db()->query('SELECT COUNT(*) FROM usuarios WHERE ativo = 1')->fetchColumn(),
    'pontos_creditados' => (int) $stmtPontos->fetchColumn(),
];
$metricas['kg_reaproveitados'] = round($metricas['entregues'] * 1.2, 1);
$metricas['co2_evitado'] = round($metricas['entregues'] * 3.6, 1);

$stmtCategorias = db()->prepare(
    'SELECT c.nome, COUNT(*) AS total
     FROM itens i
     JOIN categorias c ON c.id = i.categoria_id
     WHERE i.status = "entregue"
     ' . $wherePeriodoItens . '
     GROUP BY c.id, c.nome
     ORDER BY total DESC, c.nome ASC
     LIMIT 5'
);
$stmtCategorias->execute($paramsPeriodo);
$categorias = $stmtCategorias->fetchAll();

$movimentoMensal = db()->query(
    'SELECT DATE_FORMAT(criado_em, "%m/%Y") AS mes, COALESCE(SUM(quantidade), 0) AS total
     FROM transacoes_pontos
     WHERE tipo = "credito"
       AND criado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY YEAR(criado_em), MONTH(criado_em), mes
     ORDER BY YEAR(criado_em), MONTH(criado_em)'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= favicon_head_tags() ?>
    <title>ReUse | Painel de impacto</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=20260624">
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

        <form method="get" class="impact-filter surface-panel">
            <label>
                Período de análise
                <select name="periodo" onchange="this.form.submit()">
                    <option value="todos" <?= $periodo === 'todos' ? 'selected' : '' ?>>Todo o histórico</option>
                    <option value="90" <?= $periodo === '90' ? 'selected' : '' ?>>Últimos 90 dias</option>
                    <option value="30" <?= $periodo === '30' ? 'selected' : '' ?>>Últimos 30 dias</option>
                </select>
            </label>
        </form>

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

            <article class="stat-card">
                <span class="stat-label">Reaproveitamento estimado</span>
                <strong class="stat-value"><?= number_format($metricas['kg_reaproveitados'], 1, ',', '.') ?></strong>
                <span class="stat-unit">kg em itens reaproveitados</span>
                <p class="stat-note">Estimativa didática para comunicar impacto ambiental a partir das entregas concluídas.</p>
            </article>

            <article class="stat-card">
                <span class="stat-label">CO₂ evitado estimado</span>
                <strong class="stat-value"><?= number_format($metricas['co2_evitado'], 1, ',', '.') ?></strong>
                <span class="stat-unit">kg CO₂e</span>
                <p class="stat-note">Indicador aproximado para apoiar a leitura de sustentabilidade do MVP.</p>
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
                        <span class="section-kicker">Últimos meses</span>
                        <h2 class="section-title">Pontos por mês</h2>
                    </div>

                    <?php if (!$movimentoMensal): ?>
                        <p class="section-copy">O gráfico aparece assim que houver movimentações de pontos.</p>
                    <?php else: ?>
                        <div class="mini-bars">
                            <?php $maiorMovimento = max(array_map(static fn ($linha) => (int) $linha['total'], $movimentoMensal)); ?>
                            <?php foreach ($movimentoMensal as $linha): ?>
                                <?php $percentual = $maiorMovimento > 0 ? max(8, ((int) $linha['total'] / $maiorMovimento) * 100) : 0; ?>
                                <div class="mini-bar-row">
                                    <span><?= e($linha['mes']) ?></span>
                                    <div class="mini-bar-track"><i style="width: <?= (int) $percentual ?>%"></i></div>
                                    <strong><?= (int) $linha['total'] ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

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
