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
            <section class="panel">Item nao encontrado.</section>
        <?php else: ?>
            <section class="panel stack">
                <h1><?= e($item['titulo']) ?></h1>
                <p class="muted"><?= e($item['bairro']) ?> | Doadora: <?= e($item['doadora']) ?></p>

                <?php if ($item['foto']): ?>
                    <div class="item-photo" style="height: 320px;">
                        <img src="../<?= e($item['foto']) ?>" alt="Foto de <?= e($item['titulo']) ?>">
                    </div>
                <?php endif; ?>

                <p><?= nl2br(e($item['descricao'])) ?></p>
                <div class="badge-row">
                    <span class="badge"><?= e($item['categoria']) ?></span>
                    <span class="badge"><?= e($item['condicao']) ?></span>
                    <span class="badge"><?= (int) $item['pontos'] ?> pontos</span>
                </div>

                <?php if ((int) $item['doadora_id'] === (int) $usuario['id']): ?>
                    <a class="btn secondary" href="editar.php?id=<?= (int) $item['id'] ?>">Editar meu anuncio</a>
                <?php else: ?>
                    <a class="btn primary" href="../reservas/reservar.php?item_id=<?= (int) $item['id'] ?>">Reservar item</a>
                    <a class="btn" href="../denuncias/reportar.php?item_id=<?= (int) $item['id'] ?>">Denunciar</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
