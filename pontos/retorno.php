<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/CompraPontosRepo.php';
require_once __DIR__ . '/../app/services/MercadoPagoService.php';

$usuario = exigir_login();
$erro = '';
$mensagem = 'Recebemos o retorno do pagamento.';
$tipo = 'warning';
$compraId = (int) ($_GET['compra_id'] ?? 0);
$paymentId = (string) ($_GET['payment_id'] ?? $_GET['collection_id'] ?? '');
$paymentId = in_array(strtolower($paymentId), ['', 'null', 'undefined'], true) ? '' : $paymentId;
$erroTecnico = '';
$compra = $compraId > 0 ? CompraPontosRepo::buscarPorIdEUsuario($compraId, (int) $usuario['id']) : null;

if (!$compra) {
    $erro = 'Compra nao encontrada para esta conta.';
} else {
    try {
        if ($paymentId !== '') {
            $pagamento = MercadoPagoService::consultarPagamento($paymentId);
            CompraPontosRepo::processarPagamentoMercadoPago($pagamento);
            $compra = CompraPontosRepo::buscarPorIdEUsuario($compraId, (int) $usuario['id']);
        }

        if (($compra['status'] ?? '') === 'aprovado') {
            $tipo = 'success';
            $mensagem = 'Pagamento aprovado. Os pontos ja foram creditados na sua carteira.';
        } elseif (in_array(($compra['status'] ?? ''), ['recusado', 'cancelado', 'erro'], true)) {
            $tipo = 'error';
            $mensagem = 'O pagamento nao foi aprovado. Nenhum ponto foi creditado.';
        } else {
            $mensagem = 'O pagamento ainda esta pendente. Os pontos serao creditados automaticamente se o Mercado Pago aprovar.';
        }
    } catch (Throwable $e) {
        $erro = 'Nao foi possivel confirmar o pagamento agora. O webhook ainda pode atualizar a compra automaticamente.';
        $erroTecnico = $e->getMessage();
        error_log('[ReUse][mercadopago] Retorno: ' . $e->getMessage());
    }
}

function dinheiro_retorno(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Retorno do pagamento</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container ops-page">
        <section class="surface-panel">
            <div class="section-header">
                <span class="ops-kicker">Mercado Pago</span>
                <h1 class="ops-title">Status da compra</h1>
                <p>O ReUse credita pontos apenas quando o pagamento fica aprovado.</p>
            </div>

            <?php if ($erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
                <?php if ($erroTecnico): ?>
                    <div class="soft-note">
                        <strong>Detalhe tecnico:</strong>
                        <span><?= e($erroTecnico) ?></span>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert <?= e($tipo) ?>"><?= e($mensagem) ?></div>

                <div class="wallet-grid compact">
                    <article class="wallet-card">
                        <span class="wallet-label">Pontos</span>
                        <strong class="wallet-value"><?= (int) $compra['quantidade_pontos'] ?></strong>
                    </article>
                    <article class="wallet-card">
                        <span class="wallet-label">Total pago</span>
                        <strong class="wallet-value small"><?= e(dinheiro_retorno((float) $compra['valor_total'])) ?></strong>
                    </article>
                    <article class="wallet-card">
                        <span class="wallet-label">Status</span>
                        <strong class="wallet-value small"><?= e(CompraPontosRepo::statusLabel((string) $compra['status'])) ?></strong>
                    </article>
                </div>
            <?php endif; ?>

            <div class="action-row">
                <a class="btn primary" href="carteira.php">Ver carteira</a>
                <a class="btn secondary" href="comprar.php">Comprar pontos</a>
            </div>
        </section>
    </main>
</body>
</html>
