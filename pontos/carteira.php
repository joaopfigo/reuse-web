<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/PontosRepo.php';

$usuario = exigir_login();
$extrato = PontosRepo::extrato((int) $usuario['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Carteira de pontos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container">
        <section class="panel stack">
            <h1>Carteira de pontos</h1>
            <p class="muted">Saldo atual: <strong><?= (int) $usuario['saldo_pontos'] ?> pontos</strong></p>

            <?php if (!$extrato): ?>
                <p class="muted">Nenhuma movimentacao registrada.</p>
            <?php endif; ?>

            <?php foreach ($extrato as $transacao): ?>
                <article class="panel">
                    <strong><?= e($transacao['tipo']) ?> de <?= (int) $transacao['quantidade'] ?> pontos</strong>
                    <p class="muted"><?= e($transacao['motivo']) ?> | <?= e($transacao['criado_em']) ?></p>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
