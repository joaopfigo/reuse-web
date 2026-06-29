<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/layout.php';

$usuario = exigir_login();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= favicon_head_tags() ?>
    <title>ReUse | Segurança e confiança</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Confiança</span>
                <h1 class="ops-title">Segurança e regras da comunidade</h1>
                <p class="ops-copy">O ReUse foi desenhado para facilitar doações por pontos sem expor endereço completo, com rastreabilidade, reputação e confirmação de entrega.</p>
            </div>
        </section>

        <section class="wallet-grid">
            <article class="wallet-card featured">
                <span class="wallet-label">Conta verificada</span>
                <strong class="wallet-value small">E-mail</strong>
                <p class="wallet-note">Publicar e reservar exige confirmação de e-mail.</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Local aproximado</span>
                <strong class="wallet-value small">Bairro</strong>
                <p class="wallet-note">O sistema usa bairro/cidade e evita endereço completo.</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Pontos</span>
                <strong class="wallet-value small">Entrega</strong>
                <p class="wallet-note">Pontos só entram por entrega confirmada ou compra aprovada.</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Biosegurança</span>
                <strong class="wallet-value small">Novo</strong>
                <p class="wallet-note">Cosméticos e itens de beleza só podem ser novos e lacrados.</p>
            </article>
        </section>

        <section class="surface-panel">
            <div class="section-header">
                <h2>Como o ReUse reduz riscos</h2>
                <p>As regras abaixo organizam a troca e ajudam a comunidade a avaliar confiança antes de combinar uma retirada.</p>
            </div>

            <div class="impact-list clean">
                <div class="impact-item">
                    <div class="impact-item-copy">
                        <strong>Confirmação de entrega</strong>
                        <p>O status do item e as transações de pontos são atualizados em uma operação atômica, reduzindo duplicidade e inconsistência.</p>
                    </div>
                    <span class="impact-chip">pontos</span>
                </div>
                <div class="impact-item">
                    <div class="impact-item-copy">
                        <strong>Reputação pública</strong>
                        <p>Perfis mostram entregas concluídas, avaliações, não comparecimentos e alertas agregados de denúncia.</p>
                    </div>
                    <span class="impact-chip">perfil</span>
                </div>
                <div class="impact-item">
                    <div class="impact-item-copy">
                        <strong>Denúncia com evidência</strong>
                        <p>Usuários podem registrar problemas após interações, preservando histórico para análise de segurança.</p>
                    </div>
                    <span class="impact-chip">suporte</span>
                </div>
                <div class="impact-item">
                    <div class="impact-item-copy">
                        <strong>Duplicidade de anúncios</strong>
                        <p>O cadastro compara texto e foto para evitar anúncios repetidos da mesma pessoa.</p>
                    </div>
                    <span class="impact-chip">qualidade</span>
                </div>
            </div>
        </section>

        <section class="surface-panel">
            <div class="section-header">
                <h2>Boas práticas para retirada</h2>
                <p>Recomendações exibidas como regra de uso para reduzir risco fora do sistema.</p>
            </div>
            <ul class="guideline-list">
                <li>Combine retirada em local público e movimentado.</li>
                <li>Use o chat da reserva para alinhar horário e ponto de encontro.</li>
                <li>Não compartilhe endereço residencial completo.</li>
                <li>Finalize a entrega pelo sistema para atualizar pontuação e histórico.</li>
            </ul>
        </section>
    </main>
</body>
</html>
