<?php
// gateways/xendit.php

function xenditCreate(array $gateway, array $method, array $transaction): array
{
    $gatewayConfig = json_decode($gateway['config'] ?? null, true);

    $apiKey = $gatewayConfig['api_key'] ?? null;
    if (!$apiKey) {
        return ['error' => 'Xendit API key not configured'];
    }
    $baseUrl = $gatewayConfig['base_url'] ?? null;
    if (!$baseUrl) {
        return ['error' => 'Xendit API base_url not configured'];
    }
    $methodConfig = json_decode($method['config'] ?? null, true);

    $endpoint = $methodConfig['endpoint'] ?? null;
    if (!$endpoint) {
        return ['error' => 'Xendit API method endpoint not configured'];
    }
    $endpoint = $baseUrl . $endpoint;

    return sendCurlRequest(
        $endpoint,
        'POST',
        [], 
        $transaction,
        "{$apiKey}:"
    );
}

function xenditCancelPayment(array $config, string $externalId): array
{

    $apiKey = $config['api_key'] ?? null;
    $baseUrl = $config['base_url'] ?? 'https://api.xendit.co';
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

function xenditRefundPayment(array $config, string $externalId, array $data): array
{
    $apiKey = $config['api_key'] ?? null;
    $baseUrl = $config['base_url'] ?? 'https://api.xendit.co';
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
