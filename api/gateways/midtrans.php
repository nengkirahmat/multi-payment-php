<?php
// gateways/midtrans.php

function midtransCreate(array $gateway, $method, array $transaction): array
{
    $gatewayConfig = json_decode($gateway['config'] ?? null, true);

    $apiKey = $gatewayConfig['server_key'] ?? null;
    if (!$apiKey) {
        return ['error' => 'Midtrans API key not configured'];
    }
    $baseUrl = $gatewayConfig['base_url'] ?? null;
    if (!$baseUrl) {
        return ['error' => 'Midtrans API base_url not configured'];
    }
    $methodConfig = json_decode($method['config'] ?? null, true);

    $endpoint = $methodConfig['endpoint'] ?? null;
    if (!$endpoint) {
        return ['error' => 'Midtrans API method endpoint not configured'];
    }

    $isProduction = $gatewayConfig['is_production'];
    $baseUrl = $isProduction
    ? ($gatewayConfig['base_url'] ?? 'https://api.midtrans.com/v2')
    : ($gatewayConfig['base_url'] ?? 'https://api.sandbox.midtrans.com/v2');

    $endpoint = $baseUrl . $endpoint;

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($apiKey . ':'),
    ];
    
    return sendCurlRequest(
        $endpoint,
        'POST',
        $headers, 
        $transaction
    );
}


function midtransCancelPayment(array $config, string $externalId): array
{
    $serverKey = $config['server_key'] ?? null;
    $isSandbox = $config['is_sandbox'] ?? true;
    $baseUrl = $isSandbox ? ($config['sandbox_url'] ?? 'https://api.sandbox.midtrans.com/v2') : ($config['production_url'] ?? 'https://api.midtrans.com/v2');


    $url = "{$baseUrl}/{$externalId}/cancel";

    $headers = [
        "Authorization: Basic " . base64_encode($serverKey . ":"),
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return ['error' => $err];
    return json_decode($result, true);
}

function midtransRefundPayment(array $config, string $externalId, string $reason = ''): array
{
    $serverKey = $config['server_key'] ?? null;
    $isSandbox = $config['is_sandbox'] ?? true;
    $baseUrl = $isSandbox ? ($config['sandbox_url'] ?? 'https://api.sandbox.midtrans.com/v2') : ($config['production_url'] ?? 'https://api.midtrans.com/v2');

    $url = "{$baseUrl}/{$externalId}/refund";

    $payload = ['reason' => $reason];
    $headers = [
        "Authorization: Basic " . base64_encode($serverKey . ":"),
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return ['error' => $err];
    return json_decode($result, true);
}
