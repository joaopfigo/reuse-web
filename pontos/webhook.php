<?php
require_once __DIR__ . '/../app/repositories/CompraPontosRepo.php';
require_once __DIR__ . '/../app/services/MercadoPagoService.php';

function resposta_webhook(int $status, string $mensagem): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $mensagem;
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$json = json_decode($raw, true);
$json = is_array($json) ? $json : [];

$tipo = (string) ($_GET['type'] ?? $_GET['topic'] ?? $json['type'] ?? $json['topic'] ?? '');
$paymentId = (string) (
    $_GET['data_id']
    ?? $_GET['id']
    ?? $_GET['payment_id']
    ?? $json['data']['id']
    ?? $json['id']
    ?? ''
);

if ($tipo !== '' && !in_array($tipo, ['payment', 'payments'], true)) {
    resposta_webhook(200, 'Evento ignorado.');
}

if ($paymentId === '') {
    resposta_webhook(200, 'Sem pagamento para processar.');
}

try {
    $pagamento = MercadoPagoService::consultarPagamento($paymentId);
    CompraPontosRepo::processarPagamentoMercadoPago($pagamento);
    resposta_webhook(200, 'OK');
} catch (Throwable $e) {
    error_log('[ReUse][mercadopago] Webhook: ' . $e->getMessage());
    resposta_webhook(500, 'Erro ao processar pagamento.');
}
