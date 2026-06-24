<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/PontosRepo.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';

$usuario = exigir_login();
$extrato = PontosRepo::extrato((int) $usuario['id']);
$pontosReservados = ReservaRepo::pontosReservados((int) $usuario['id']);
$saldoDisponivel = ReservaRepo::saldoDisponivel($usuario);
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
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container ops-page">
        <section class="ops-hero">
            <div class="ops-hero-main">
                <span class="ops-kicker">Carteira</span>
                <h1 class="ops-title">Pontos e movimentações</h1>
                <p class="ops-copy">Acompanhe quanto já entrou, quanto foi utilizado e o histórico completo das entregas confirmadas no sistema.</p>
            </div>
        </section>

        <section class="wallet-grid">
            <article class="wallet-card featured">
                <span class="wallet-label">Saldo atual</span>
                <strong class="wallet-value"><?= (int) $usuario['saldo_pontos'] ?></strong>
                <p class="wallet-note">saldo total da conta</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Disponível para reservar</span>
                <strong class="wallet-value"><?= $saldoDisponivel ?></strong>
                <p class="wallet-note"><?= $pontosReservados ?> ponto(s) comprometidos em reservas ativas</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Pontos recebidos</span>
                <strong class="wallet-value">+<?= $creditos ?></strong>
                <p class="wallet-note">somados às entregas concluídas</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Pontos usados</span>
                <strong class="wallet-value">-<?= $debitos ?></strong>
                <p class="wallet-note">consumidos em reservas confirmadas</p>
            </article>
        </section>

        <section class="surface-panel">
            <div class="section-header">
                <h2>Extrato</h2>
                <p>Veja a ordem das movimentações e identifique rapidamente de onde veio cada crédito ou débito.</p>
            </div>

            <?php if (!$extrato): ?>
                <div class="empty-card">Nenhuma movimentação registrada ainda.</div>
            <?php endif; ?>

            <div class="ledger-list">
                <?php foreach ($extrato as $t): ?>
                    <article class="ledger-item transacao <?= e($t['tipo']) ?>">
                        <div class="transacao-icone">
                            <?= $t['tipo'] === 'credito' ? '+' : '-' ?>
                        </div>
                        <div class="transacao-info">
                            <p class="transacao-valor"><?= $t['tipo'] === 'credito' ? '+' : '-' ?><?= (int) $t['quantidade'] ?> pontos</p>
                            <p class="muted"><?= e($t['motivo']) ?></p>
                            <small class="muted"><?= e(formatar_data_hora($t['criado_em'])) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
