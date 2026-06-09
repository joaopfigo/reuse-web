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
            <div>
                <p class="muted">Saldo atual</p>
                <p class="saldo-destaque">
                    <?= (int) $usuario['saldo_pontos'] ?>
                    <small>pontos</small>
                </p>
            </div>

            <hr class="divider">

            <h2>Extrato</h2>

            <?php if (!$extrato): ?>
                <p class="muted">Nenhuma movimentação registrada ainda.</p>
            <?php endif; ?>

            <?php foreach ($extrato as $t): ?>
                <article class="panel transacao <?= e($t['tipo']) ?>">
                    <div class="transacao-icone">
                        <?= $t['tipo'] === 'credito' ? '+' : '−' ?>
                    </div>
                    <div class="transacao-info">
                        <p class="transacao-valor">
                            <?= $t['tipo'] === 'credito' ? '+' : '−' ?><?= (int) $t['quantidade'] ?> pontos
                        </p>
                        <p class="muted"><?= e($t['motivo']) ?></p>
                        <small class="muted"><?= e($t['criado_em']) ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
