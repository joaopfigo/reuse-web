<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';

$usuario = exigir_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    NotificacaoRepo::marcarTodasLidas((int) $usuario['id']);
    flash_set('success', 'Notificações marcadas como lidas.');
    header('Location: ' . app_url('notificacoes/index.php'));
    exit;
}

$notificacoes = NotificacaoRepo::listar((int) $usuario['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Notificações</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260615">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Alertas da conta</span>
                <h1 class="ops-title">Notificações</h1>
                <p class="ops-copy">Acompanhe respostas, confirmações, cancelamentos e atualizações importantes sem perder o contexto das suas ações.</p>
            </div>
            <div class="ops-hero-side">
                <form method="post" class="inline-form">
                    <?= csrf_input() ?>
                    <button type="submit" class="btn secondary">Marcar todas como lidas</button>
                </form>
            </div>
        </section>

        <?= flash_html() ?>

        <?php if (!$notificacoes): ?>
            <div class="empty-card">Sem notificações por enquanto.</div>
        <?php endif; ?>

        <section class="notification-list">
            <?php foreach ($notificacoes as $n): ?>
                <article class="notification-card <?= $n['lida'] ? '' : 'is-unread' ?>">
                    <p><?= e($n['mensagem']) ?></p>
                    <time><?= e(formatar_data_hora($n['criada_em'])) ?></time>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
