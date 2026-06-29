<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/CompraPontosRepo.php';
require_once __DIR__ . '/../app/repositories/PontosRepo.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';

$usuario = exigir_login();
$extrato = PontosRepo::extrato((int) $usuario['id']);
$compras = CompraPontosRepo::listarPorUsuario((int) $usuario['id'], 5);
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

function dinheiro_carteira(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
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
                <h1 class="ops-title">Pontos e movimentacoes</h1>
                <p class="ops-copy">Acompanhe pontos recebidos por entregas confirmadas, compras aprovadas e usos em reservas concluidas.</p>
            </div>
            <div class="ops-hero-side">
                <a class="btn primary" href="comprar.php">Comprar pontos</a>
            </div>
        </section>

        <div class="alert warning">
            Os itens continuam sendo doados por pontos. A compra de pontos e opcional; quando houver taxa de pagamento, ela ja aparece incluida no valor final antes da confirmacao.
        </div>

        <section class="wallet-grid">
            <article class="wallet-card featured">
                <span class="wallet-label">Saldo atual</span>
                <strong class="wallet-value"><?= (int) $usuario['saldo_pontos'] ?></strong>
                <p class="wallet-note">saldo total da conta</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Disponivel para reservar</span>
                <strong class="wallet-value"><?= $saldoDisponivel ?></strong>
                <p class="wallet-note"><?= $pontosReservados ?> ponto(s) comprometidos em reservas ativas</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Pontos recebidos</span>
                <strong class="wallet-value">+<?= $creditos ?></strong>
                <p class="wallet-note">entregas confirmadas e compras aprovadas</p>
            </article>
            <article class="wallet-card">
                <span class="wallet-label">Pontos usados</span>
                <strong class="wallet-value">-<?= $debitos ?></strong>
                <p class="wallet-note">consumidos em reservas confirmadas</p>
            </article>
        </section>

        <section class="surface-panel">
            <div class="section-header">
                <h2>Compras de pontos</h2>
                <p>Pagamentos pendentes, recusados ou cancelados nao geram credito na carteira.</p>
            </div>

            <?php if (!$compras): ?>
                <div class="empty-card">Nenhuma compra registrada ainda.</div>
            <?php endif; ?>

            <div class="ledger-list">
                <?php foreach ($compras as $compra): ?>
                    <article class="ledger-item">
                        <div class="transacao-info">
                            <p class="transacao-valor"><?= (int) $compra['quantidade_pontos'] ?> pontos - <?= e(dinheiro_carteira((float) $compra['valor_total'])) ?></p>
                            <p class="muted">Status: <?= e(CompraPontosRepo::statusLabel((string) $compra['status'])) ?></p>
                            <small class="muted"><?= e(formatar_data_hora($compra['criado_em'])) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="surface-panel">
            <div class="section-header">
                <h2>Extrato</h2>
                <p>Veja a ordem das movimentacoes e identifique rapidamente de onde veio cada credito ou debito.</p>
            </div>

            <?php if (!$extrato): ?>
                <div class="empty-card">Nenhuma movimentacao registrada ainda.</div>
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
