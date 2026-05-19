<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/config/db.php';

$usuario = exigir_login();

$stmt = db()->prepare('SELECT r.*, i.titulo, i.pontos, u.nome AS doadora
                       FROM reservas r
                       JOIN itens i ON i.id = r.item_id
                       JOIN usuarios u ON u.id = i.doadora_id
                       WHERE r.receptora_id = :usuario_id
                       ORDER BY r.criada_em DESC');
$stmt->execute([':usuario_id' => $usuario['id']]);
$reservas = $stmt->fetchAll();
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

            <?php if (!$reservas): ?>
                <p class="muted">Voce ainda nao possui reservas.</p>
            <?php endif; ?>

            <?php foreach ($reservas as $reserva): ?>
                <article class="panel">
                    <h2><?= e($reserva['titulo']) ?></h2>
                    <p class="muted">Doadora: <?= e($reserva['doadora']) ?> | Status: <?= e($reserva['status']) ?> | <?= (int) $reserva['pontos'] ?> pontos</p>
                    <?php if ($reserva['status'] !== 'entregue'): ?>
                        <a class="btn primary" href="confirmar.php?id=<?= (int) $reserva['id'] ?>">Confirmar entrega</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
