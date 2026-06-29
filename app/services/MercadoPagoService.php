<?php

declare(strict_types=1);

class MercadoPagoService
{
    private const API_BASE = 'https://api.mercadopago.com';
    private const PRECO_PONTO = 3.00;

    public static function precoPonto(): float
    {
        return self::PRECO_PONTO;
    }

    public static function calcularValores(int $quantidade): array
    {
        $quantidade = max(1, $quantidade);
        $config = self::config(false) ?? [];
        $valorBase = round($quantidade * self::PRECO_PONTO, 2);
        $taxaPercentual = (float) ($config['taxa_percentual'] ?? 0);
        $taxaFixa = (float) ($config['taxa_fixa'] ?? 0);
        $taxa = round(($valorBase * ($taxaPercentual / 100)) + $taxaFixa, 2);
        $valorTotal = round($valorBase + $taxa, 2);

        return [
            'quantidade_pontos' => $quantidade,
            'preco_ponto' => self::PRECO_PONTO,
            'taxa_percentual' => $taxaPercentual,
            'taxa_fixa' => $taxaFixa,
            'valor_base' => $valorBase,
            'taxa' => $taxa,
            'valor_total' => $valorTotal,
        ];
    }

    public static function criarPreferencia(array $compra, string $baseUrl): array
    {
        $config = self::config();
        $baseUrl = rtrim($baseUrl, '/');
        $compraId = (int) $compra['id'];

        $payload = [
            'items' => [[
                'id' => 'reuse-pontos-' . $compraId,
                'title' => 'Pontos ReUse',
                'description' => 'Compra opcional de ' . (int) $compra['quantidade_pontos'] . ' ponto(s) para reservar itens doados no ReUse.',
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => (float) $compra['valor_total'],
            ]],
            'external_reference' => (string) $compra['referencia_externa'],
            'metadata' => [
                'compra_pontos_id' => $compraId,
                'usuario_id' => (int) $compra['usuario_id'],
                'quantidade_pontos' => (int) $compra['quantidade_pontos'],
            ],
            'back_urls' => [
                'success' => $baseUrl . app_url('pontos/retorno.php?compra_id=' . $compraId . '&resultado=success'),
                'pending' => $baseUrl . app_url('pontos/retorno.php?compra_id=' . $compraId . '&resultado=pending'),
                'failure' => $baseUrl . app_url('pontos/retorno.php?compra_id=' . $compraId . '&resultado=failure'),
            ],
            'notification_url' => $baseUrl . app_url('pontos/webhook.php'),
            'auto_return' => 'approved',
        ];

        return self::request('POST', '/checkout/preferences', $payload, $config['access_token']);
    }

    public static function consultarPagamento(string $paymentId): array
    {
        $config = self::config();

        return self::request('GET', '/v1/payments/' . rawurlencode($paymentId), null, $config['access_token']);
    }

    public static function usarSandbox(): bool
    {
        $config = self::config(false) ?? [];

        return (bool) ($config['sandbox'] ?? true);
    }

    private static function config(bool $obrigatorio = true): ?array
    {
        $caminho = dirname(__DIR__, 3) . '/private_config/mercadopago.credentials.php';
        if (is_file($caminho)) {
            $config = require $caminho;
            if (is_array($config)) {
                if ($obrigatorio && empty($config['access_token'])) {
                    throw new RuntimeException('Informe o access_token do Mercado Pago em private_config/mercadopago.credentials.php.');
                }

                return $config;
            }
        }

        if ($obrigatorio) {
            throw new RuntimeException('Configure private_config/mercadopago.credentials.php antes de comprar pontos.');
        }

        return null;
    }

    private static function request(string $metodo, string $endpoint, ?array $payload, string $accessToken): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('A extensao cURL do PHP precisa estar ativa para integrar com o Mercado Pago.');
        }

        $curl = curl_init(self::API_BASE . $endpoint);
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($payload !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $resposta = curl_exec($curl);
        $erro = curl_error($curl);
        $statusHttp = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($resposta === false) {
            throw new RuntimeException('Falha ao conectar ao Mercado Pago: ' . $erro);
        }

        $dados = json_decode($resposta, true);
        if (!is_array($dados)) {
            throw new RuntimeException('Resposta invalida do Mercado Pago.');
        }

        if ($statusHttp < 200 || $statusHttp >= 300) {
            $mensagem = $dados['message'] ?? $dados['error'] ?? 'Erro na API do Mercado Pago.';
            error_log('[ReUse][mercadopago] HTTP ' . $statusHttp . ': ' . $mensagem);
            throw new RuntimeException('Mercado Pago recusou a operacao: ' . $mensagem);
        }

        return $dados;
    }
}
