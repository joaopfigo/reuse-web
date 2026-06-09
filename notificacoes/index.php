<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';

$usuario = exigir_login();
NotificacaoRepo::marcarTodasLidas((int) $usuario['id']);
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
            <h1>Notificações</h1>

            <?php if (!$notificacoes): ?>
                <p class="muted">Sem notificações.</p>
            <?php endif; ?>

            <?php foreach ($notificacoes as $n): ?>
                <article class="panel <?= $n['lida'] ? 'muted' : '' ?>">
                    <p><?= e($n['mensagem']) ?></p>
                    <small class="muted"><?= e($n['criada_em']) ?></small>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
