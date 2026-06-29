<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/CompraPontosRepo.php';
require_once __DIR__ . '/../app/services/MercadoPagoService.php';

$usuario = exigir_login();
$erro = '';
$quantidade = (int) ($_POST['quantidade'] ?? $_GET['quantidade'] ?? 10);
$quantidade = max(1, min(200, $quantidade));
$valores = MercadoPagoService::calcularValores($quantidade);
$compras = CompraPontosRepo::listarPorUsuario((int) $usuario['id']);

function dinheiro(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function url_base_atual(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();

    try {
        $compraId = CompraPontosRepo::criar((int) $usuario['id'], $valores);
        $compra = CompraPontosRepo::buscarPorId($compraId);

        if (!$compra) {
            throw new RuntimeException('Nao foi possivel criar a compra.');
        }

        $preferencia = MercadoPagoService::criarPreferencia($compra, url_base_atual());
        CompraPontosRepo::atualizarPreferencia($compraId, $preferencia);

        $urlPagamento = MercadoPagoService::usarSandbox()
            ? ($preferencia['sandbox_init_point'] ?? $preferencia['init_point'] ?? '')
            : ($preferencia['init_point'] ?? $preferencia['sandbox_init_point'] ?? '');

        if ($urlPagamento === '') {
            throw new RuntimeException('O Mercado Pago nao retornou a URL de pagamento.');
        }

        header('Location: ' . $urlPagamento);
        exit;
    } catch (Throwable $e) {
        if (!empty($compraId)) {
            CompraPontosRepo::marcarErro((int) $compraId, $e->getMessage());
        }
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= favicon_head_tags() ?>
    <title>ReUse | Comprar pontos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Compra opcional</span>
                <h1 class="ops-title">Comprar pontos</h1>
                <p class="ops-copy">Os itens do ReUse continuam sendo doados por pontos. A compra e opcional e serve para quem quer reservar itens antes de receber pontos por entregas confirmadas.</p>
            </div>
        </section>

        <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>

        <section class="form-layout">
            <section class="surface-panel form-main">
                <div class="section-header">
                    <h2>Escolha a quantidade</h2>
                    <p>Cada ponto custa <?= e(dinheiro(MercadoPagoService::precoPonto())) ?>. As taxas de pagamento configuradas ja aparecem no valor final antes da confirmacao.</p>
                </div>

                <form method="get" class="grid-2">
                    <label>
                        Quantidade de pontos
                        <input type="number" name="quantidade" min="1" max="200" value="<?= (int) $quantidade ?>" required>
                    </label>
                    <div class="action-row align-end">
                        <button class="btn secondary" type="submit">Calcular valor</button>
                    </div>
                </form>

                <div class="wallet-grid compact">
                    <article class="wallet-card">
                        <span class="wallet-label">Pontos</span>
                        <strong class="wallet-value"><?= (int) $valores['quantidade_pontos'] ?></strong>
                        <p class="wallet-note"><?= e(dinheiro((float) $valores['preco_ponto'])) ?> por ponto</p>
                    </article>
                    <article class="wallet-card">
                        <span class="wallet-label">Valor base</span>
                        <strong class="wallet-value small"><?= e(dinheiro((float) $valores['valor_base'])) ?></strong>
                        <p class="wallet-note">quantidade x preco do ponto</p>
                    </article>
                    <article class="wallet-card">
                        <span class="wallet-label">Taxa</span>
                        <strong class="wallet-value small"><?= e(dinheiro((float) $valores['taxa'])) ?></strong>
                        <p class="wallet-note"><?= number_format((float) $valores['taxa_percentual'], 2, ',', '.') ?>% + <?= e(dinheiro((float) $valores['taxa_fixa'])) ?></p>
                    </article>
                    <article class="wallet-card featured">
                        <span class="wallet-label">Total final</span>
                        <strong class="wallet-value small"><?= e(dinheiro((float) $valores['valor_total'])) ?></strong>
                        <p class="wallet-note">valor enviado ao Checkout Pro</p>
                    </article>
                </div>

                <form method="post" class="action-row">
                    <?= csrf_input() ?>
                    <input type="hidden" name="quantidade" value="<?= (int) $quantidade ?>">
                    <button class="btn primary" type="submit">Confirmar e ir para o Mercado Pago</button>
                    <a class="btn secondary" href="carteira.php">Voltar para carteira</a>
                </form>
            </section>

            <aside class="form-side">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Como funciona</h2>
                        <p>Os pontos comprados so entram na carteira quando o Mercado Pago retornar pagamento aprovado.</p>
                    </div>
                    <ul class="guideline-list">
                        <li>Pagamento pendente, recusado ou cancelado nao gera pontos.</li>
                        <li>As entregas confirmadas continuam gerando pontos para quem doa.</li>
                        <li>A compra de pontos e opcional e as taxas ja estao incluidas no total exibido.</li>
                    </ul>
                </section>
            </aside>
        </section>

        <section class="surface-panel">
            <div class="section-header">
                <h2>Compras recentes</h2>
                <p>Acompanhe os ultimos pedidos de compra feitos por esta conta.</p>
            </div>

            <?php if (!$compras): ?>
                <div class="empty-card">Nenhuma compra de pontos registrada ainda.</div>
            <?php endif; ?>

            <div class="ledger-list">
                <?php foreach ($compras as $compra): ?>
                    <article class="ledger-item">
                        <div class="transacao-info">
                            <p class="transacao-valor"><?= (int) $compra['quantidade_pontos'] ?> pontos - <?= e(dinheiro((float) $compra['valor_total'])) ?></p>
                            <p class="muted">Status: <?= e(CompraPontosRepo::statusLabel((string) $compra['status'])) ?></p>
                            <small class="muted"><?= e(formatar_data_hora($compra['criado_em'])) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
