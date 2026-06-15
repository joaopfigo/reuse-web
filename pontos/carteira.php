<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/PontosRepo.php';

$usuario = exigir_login();
$extrato = PontosRepo::extrato((int) $usuario['id']);
$creditos = 0;
$debitos = 0;

foreach ($extrato as $movimento) {
    if (($movimento['tipo'] ?? '') === 'credito') {
        $creditos += (int) $movimento['quantidade'];
    } elseif (($movimento['tipo'] ?? '') === 'debito') {
        $debitos += (int) $movimento['quantidade'];
    }
}
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
        <section class="summary-grid">
            <article class="summary-card">
                <span class="summary-label">Saldo atual</span>
                <strong><?= (int) $usuario['saldo_pontos'] ?> pontos</strong>
            </article>
            <article class="summary-card">
                <span class="summary-label">Pontos recebidos</span>
                <strong>+<?= $creditos ?></strong>
            </article>
            <article class="summary-card">
                <span class="summary-label">Pontos usados</span>
                <strong>-<?= $debitos ?></strong>
            </article>
        </section>

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
                        <?= $t['tipo'] === 'credito' ? '+' : '-' ?>
                    </div>
                    <div class="transacao-info">
                        <p class="transacao-valor">
                            <?= $t['tipo'] === 'credito' ? '+' : '-' ?><?= (int) $t['quantidade'] ?> pontos
                        </p>
                        <p class="muted"><?= e($t['motivo']) ?></p>
                        <small class="muted"><?= e(formatar_data_hora($t['criado_em'])) ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
