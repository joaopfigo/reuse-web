<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/AvaliacaoRepo.php';
require_once __DIR__ . '/../app/repositories/DenunciaRepo.php';
require_once __DIR__ . '/../app/repositories/UsuarioRepo.php';

$usuario = exigir_login();
$perfilId = (int) ($_GET['id'] ?? 0);
$perfil = $perfilId > 0 ? UsuarioRepo::resumoPublico($perfilId) : null;

if (!$perfil) {
    flash_set('error', 'Perfil não encontrado.');
    header('Location: ' . app_url('itens/listar.php'));
    exit;
}

$avaliacoes = AvaliacaoRepo::recebidas($perfilId);
$positivas = AvaliacaoRepo::positivas($perfilId);
$denuncias = DenunciaRepo::estatisticasPublicas($perfilId);
$media = $perfil['avaliacao_media'] ?? null;
$totalAvaliacoes = count($avaliacoes);
$selo = ((int) $perfil['itens_doados'] >= 3 && (int) $perfil['denuncias_recebidas'] === 0 && (int) $perfil['no_show_count'] === 0)
    ? 'Perfil com bom histórico'
    : 'Perfil em construção';

function motivo_denuncia_label(string $motivo): string
{
    return match ($motivo) {
        'item_falso' => 'Item falso ou enganoso',
        'comportamento' => 'Comportamento inadequado',
        'no_show' => 'Não comparecimento',
        default => 'Outro',
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Perfil público</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
    <?= pwa_head_tags() ?>
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Perfil público</span>
                <h1 class="ops-title"><?= e($perfil['nome']) ?></h1>
                <p class="ops-copy"><?= e($perfil['bairro']) ?>, <?= e($perfil['cidade']) ?> · <?= e($selo) ?></p>
            </div>
            <div class="ops-hero-side">
                <div class="ops-side-card">
                    <span>Avaliação média</span>
                    <strong><?= $media !== null ? e(number_format((float) $media, 1, ',', '.')) . ' / 5' : 'Sem avaliações' ?></strong>
                </div>
            </div>
        </section>

        <section class="wallet-grid">
            <article class="wallet-card featured">
                <span class="wallet-label">Itens doados</span>
                <strong class="wallet-value"><?= (int) $perfil['itens_doados'] ?></strong>
                <p class="wallet-note">entregas confirmadas</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Itens recebidos</span>
                <strong class="wallet-value"><?= (int) $perfil['itens_recebidos'] ?></strong>
                <p class="wallet-note">reservas concluídas como receptor(a)</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Não comparecimentos</span>
                <strong class="wallet-value"><?= (int) $perfil['no_show_count'] ?></strong>
                <p class="wallet-note">registro(s) no histórico</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Alertas de segurança</span>
                <strong class="wallet-value"><?= (int) $denuncias['total'] ?></strong>
                <p class="wallet-note">denúncia(s) registradas para análise</p>
            </article>
        </section>

        <section class="detail-layout">
            <div class="detail-main">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Feedbacks positivos</h2>
                        <p>Comentários com nota 4 ou 5 recebidos após entregas confirmadas.</p>
                    </div>

                    <?php if (!$positivas): ?>
                        <div class="empty-card">Ainda não há feedbacks positivos registrados.</div>
                    <?php endif; ?>

                    <div class="feedback-list">
                        <?php foreach ($positivas as $avaliacao): ?>
                            <article class="feedback-card">
                                <div>
                                    <strong><?= (int) $avaliacao['nota'] ?> / 5</strong>
                                    <span><?= e(formatar_data_hora($avaliacao['criada_em'])) ?></span>
                                </div>
                                <p><?= $avaliacao['comentario'] ? e($avaliacao['comentario']) : 'Avaliação positiva sem comentário.' ?></p>
                                <small>Entrega: <?= e($avaliacao['item_titulo']) ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <aside class="detail-media">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Resumo de confiança</h2>
                        <p>Use estes sinais como apoio antes de combinar uma retirada.</p>
                    </div>

                    <div class="donor-info-grid">
                        <div class="donor-stat">
                            <span class="donor-stat-label">Avaliações recebidas</span>
                            <strong class="donor-stat-value"><?= $totalAvaliacoes ?></strong>
                        </div>
                        <div class="donor-stat">
                            <span class="donor-stat-label">Localidade aproximada</span>
                            <strong class="donor-stat-value"><?= e($perfil['bairro']) ?>, <?= e($perfil['cidade']) ?></strong>
                        </div>
                    </div>
                </section>

                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Denúncias registradas</h2>
                        <p>Os dados abaixo são indicadores agregados. O conteúdo dos relatos não é exposto publicamente.</p>
                    </div>

                    <?php if (!$denuncias['motivos']): ?>
                        <div class="soft-note">Nenhuma denúncia registrada contra este perfil.</div>
                    <?php else: ?>
                        <div class="impact-list clean">
                            <?php foreach ($denuncias['motivos'] as $motivo): ?>
                                <div class="impact-item">
                                    <div class="impact-item-copy">
                                        <strong><?= e(motivo_denuncia_label((string) $motivo['motivo'])) ?></strong>
                                        <p>Registro para acompanhamento de segurança.</p>
                                    </div>
                                    <span class="impact-chip"><?= (int) $motivo['total'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
