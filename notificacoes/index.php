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
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <div class="page-header">
                <div>
                    <h1>Notificações</h1>
                    <p class="muted">Acompanhe respostas, confirmações e movimentos importantes da sua conta.</p>
                </div>
                <form method="post" class="inline-form">
                    <?= csrf_input() ?>
                    <button type="submit" class="btn secondary">Marcar todas como lidas</button>
                </form>
            </div>

            <?= flash_html() ?>

            <?php if (!$notificacoes): ?>
                <p class="muted empty-state">Sem notificações por enquanto.</p>
            <?php endif; ?>

            <?php foreach ($notificacoes as $n): ?>
                <article class="panel <?= $n['lida'] ? 'muted' : '' ?>">
                    <p><?= e($n['mensagem']) ?></p>
                    <small class="muted"><?= e(formatar_data_hora($n['criada_em'])) ?></small>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
