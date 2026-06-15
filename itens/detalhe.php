<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$item = ItemRepo::buscarPorId((int) ($_GET['id'] ?? 0));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Detalhe do item</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container">
        <?php if (!$item): ?>
            <section class="panel empty-state">Item não encontrado.</section>
        <?php else: ?>
            <section class="panel stack">
                <div class="page-header">
                    <div>
                        <h1><?= e($item['titulo']) ?></h1>
                        <p class="muted"><?= e($item['bairro']) ?> · Doadora: <?= e($item['doadora']) ?></p>
                    </div>
                    <span class="<?= e(status_badge_class((string) $item['status'])) ?>"><?= e(status_label((string) $item['status'])) ?></span>
                </div>

                <?php if ($item['foto']): ?>
                    <div class="item-photo item-photo-large">
                        <img src="../<?= e($item['foto']) ?>" alt="Foto de <?= e($item['titulo']) ?>">
                    </div>
                <?php endif; ?>

                <p><?= nl2br(e($item['descricao'])) ?></p>

                <div class="badge-row">
                    <span class="badge"><?= e($item['categoria']) ?></span>
                    <span class="badge"><?= e(status_label((string) $item['condicao'])) ?></span>
                    <span class="badge"><?= (int) $item['pontos'] ?> pontos</span>
                </div>

                <?php if ((int) $item['doadora_id'] === (int) $usuario['id']): ?>
                    <div class="status-callout">
                        Este item é seu. Você pode editar o anúncio enquanto ele estiver disponível ou pausado.
                    </div>
                    <div class="action-row">
                        <a class="btn secondary" href="editar.php?id=<?= (int) $item['id'] ?>">Editar meu anúncio</a>
                    </div>
                <?php else: ?>
                    <div class="status-callout">
                        Ao reservar, você receberá o acompanhamento da retirada em "Minhas reservas".
                    </div>
                    <div class="action-row">
                        <form method="post" action="../reservas/reservar.php" class="inline-form">
                            <?= csrf_input() ?>
                            <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                            <button class="btn primary" type="submit">Reservar item</button>
                        </form>
                        <a class="btn danger" href="../denuncias/reportar.php?item_id=<?= (int) $item['id'] ?>">Denunciar item</a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
