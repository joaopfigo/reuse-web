<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';

$usuario = exigir_login();
$reservas = ReservaRepo::minhasDaReceptora((int) $usuario['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Minhas reservas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Reservas</span>
                <h1 class="ops-title">Minhas reservas</h1>
                <p class="ops-copy">Acompanhe aceite, retirada, código de confirmação e a etapa final da entrega em uma visão mais clara.</p>
            </div>
        </section>

        <?= flash_html() ?>

        <?php if (!empty($usuario['bloqueada_ate']) && strtotime($usuario['bloqueada_ate']) > time()): ?>
            <div class="alert error">
                Sua conta está temporariamente bloqueada por não comparecimentos repetidos. Você poderá fazer novas reservas a partir de
                <strong><?= e(formatar_data_hora($usuario['bloqueada_ate'])) ?></strong>.
            </div>
        <?php endif; ?>

        <?php if (!$reservas): ?>
            <div class="empty-card">Você ainda não possui reservas.</div>
        <?php endif; ?>

        <section class="list-grid">
            <?php foreach ($reservas as $r): ?>
                <article class="list-card">
                    <div class="list-card-head">
                        <div>
                            <h2><?= e($r['titulo']) ?></h2>
                            <p class="list-card-meta">Doador(a): <?= e($r['doadora']) ?> · <?= (int) $r['pontos'] ?> pontos</p>
                        </div>
                        <span class="<?= e(status_badge_class((string) $r['status'])) ?>"><?= e(status_label((string) $r['status'])) ?></span>
                    </div>

                    <div class="list-card-body">
                        <?php if ($r['status'] === 'aceita' && $r['local_retirada']): ?>
                            <div class="soft-note">
                                <strong>Local:</strong> <?= e($r['local_retirada']) ?><br>
                                <strong>Data:</strong> <?= e(formatar_data_hora($r['data_retirada'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($r['status'] === 'aceita'): ?>
                            <div class="soft-note">No encontro, peça o código de 6 caracteres para confirmar a entrega.</div>
                        <?php endif; ?>
                    </div>

                    <div class="list-card-actions">
                        <?php if (in_array($r['status'], ['pendente', 'aceita'], true)): ?>
                            <a class="btn" href="chat.php?id=<?= (int) $r['id'] ?>">Mensagens</a>
                            <form method="post" action="cancelar.php" class="inline-form" onsubmit="return confirm('Cancelar esta reserva?');">
                                <?= csrf_input() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="btn danger" type="submit">Cancelar reserva</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($r['status'] === 'aceita'): ?>
                            <a class="btn primary" href="confirmar.php?id=<?= (int) $r['id'] ?>">Confirmar entrega</a>
                        <?php endif; ?>

                        <?php if ($r['status'] === 'entregue'): ?>
                            <a class="btn" href="../avaliacoes/avaliar.php?reserva_id=<?= (int) $r['id'] ?>">Avaliar doador(a)</a>
                        <?php endif; ?>

                        <?php if (in_array($r['status'], ['aceita', 'entregue', 'no_show'], true)): ?>
                            <a class="btn danger" href="../denuncias/reportar.php?reserva_id=<?= (int) $r['id'] ?>&amp;denunciada_id=<?= (int) $r['doadora_id'] ?>">Denunciar comportamento</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
