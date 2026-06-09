<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';

$usuario  = exigir_login();
$reservas = ReservaRepo::minhasDaReceptora((int) $usuario['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Minhas reservas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <h1>Minhas reservas</h1>
            <?= flash_html() ?>

            <?php if (!empty($usuario['bloqueada_ate']) && strtotime($usuario['bloqueada_ate']) > time()): ?>
                <div class="alert error">
                    Sua conta está temporariamente bloqueada por não comparecimentos repetidos.
                    Você poderá fazer novas reservas a partir de
                    <strong><?= e(date('d/m/Y \à\s H:i', strtotime($usuario['bloqueada_ate']))) ?></strong>.
                </div>
            <?php endif; ?>

            <?php if (!$reservas): ?>
                <p class="muted">Você ainda não possui reservas.</p>
            <?php endif; ?>

            <?php foreach ($reservas as $r): ?>
                <article class="panel">
                    <h2><?= e($r['titulo']) ?></h2>
                    <p class="muted">
                        Doadora: <?= e($r['doadora']) ?> |
                        Status: <strong><?= e($r['status']) ?></strong> |
                        <?= (int) $r['pontos'] ?> pontos
                    </p>

                    <?php if ($r['status'] === 'aceita' && $r['local_retirada']): ?>
                        <p>
                            Local: <strong><?= e($r['local_retirada']) ?></strong><br>
                            Data: <strong><?= e($r['data_retirada']) ?></strong>
                        </p>
                    <?php endif; ?>

                    <?php if (in_array($r['status'], ['pendente', 'aceita'], true)): ?>
                        <a class="btn" href="chat.php?id=<?= (int) $r['id'] ?>">Mensagens</a>
                        <a class="btn danger" href="cancelar.php?id=<?= (int) $r['id'] ?>"
                           onclick="return confirm('Cancelar esta reserva?')">Cancelar reserva</a>
                    <?php endif; ?>

                    <?php if ($r['status'] === 'aceita'): ?>
                        <a class="btn primary" href="confirmar.php?id=<?= (int) $r['id'] ?>">Confirmar entrega</a>
                    <?php endif; ?>

                    <?php if ($r['status'] === 'entregue'): ?>
                        <a class="btn" href="../avaliacoes/avaliar.php?reserva_id=<?= (int) $r['id'] ?>">Avaliar doadora</a>
                    <?php endif; ?>

                    <?php if (in_array($r['status'], ['aceita', 'entregue', 'no_show'], true)): ?>
                        <a class="btn danger"
                           href="../denuncias/reportar.php?reserva_id=<?= (int) $r['id'] ?>&amp;denunciada_id=<?= (int) $r['doadora_id'] ?>">
                            Denunciar comportamento
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>

