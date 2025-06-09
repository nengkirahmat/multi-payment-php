<?php
// gateways/tripay.php

function tripayCreate(array $gateway, array $method, array $transaction): array
{
    $gatewayConfig = json_decode($gateway['config'] ?? null, true);

    $apiKey = $gatewayConfig['api_key'] ?? null;
    if (!$apiKey) {
        return ['error' => 'Tripay API key not configured'];
    }
    $baseUrl = $gatewayConfig['base_url'] ?? null;
    if (!$baseUrl) {
        return ['error' => 'Tripay API base_url not configured'];
    }

    $merchantCode=$gatewayConfig['merchant_code'];
    if (!$merchantCode) {
        return ['error' => 'Tripay API merchant_code not configured'];
    }
    $privateKey=$gatewayConfig['private_key'];
    if (!$privateKey) {
        return ['error' => 'Tripay API private_key not configured'];
    }

    $methodConfig = json_decode($method['config'] ?? null, true);

    $endpoint = $methodConfig['endpoint'] ?? null;
    if (!$endpoint) {
        return ['error' => 'Tripay API method endpoint not configured'];
    }
    
    $endpoint = $baseUrl . $endpoint;


    $merchantRef=$transaction['merchant_ref'];
    $amount=$transaction['amount'];
    
    $signature = hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);
    $transaction['signature'] = $signature;
    return sendCurlRequest(
        $endpoint,
        'POST',
        ["Authorization: Bearer " . $apiKey], // tambahan header jika ada
        $transaction
    );
}

function tripayCancelPayment(array $config, string $externalId): array
{

    $apiKey = $config['api_key'] ?? null;
    $baseUrl = $config['base_url'] ?? 'https://api.tripay.co';
    $baseUrl = $baseUrl . "/invoices/{$externalId}/expire!";
    //EXPIRED INVOICE
    return sendCurlRequest(
        $baseUrl,
        'POST',
        [],
        [],
        "{$apiKey}:"
    );
}

function tripayRefundPayment(array $config, string $externalId, array $data): array
{
    $apiKey = $config['api_key'] ?? null;
    $baseUrl = $config['base_url'] ?? 'https://api.tripay.co';
    $baseUrl = $baseUrl . "/invoices/{$externalId}/refunds";
    $body = $data['body'];
    return sendCurlRequest(
        $baseUrl,
        'POST',
        [],
        $body,
        "{$apiKey}:"
    );
}
