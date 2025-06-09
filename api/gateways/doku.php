<?php

function dokuCreate(array $gateway, array $method, array $transaction): array
{
    $gatewayConfig = json_decode($gateway['config'] ?? '{}', true);
    $methodConfig = json_decode($method['config'] ?? '{}', true);
    $body = $transaction ?? [];

    // Validasi config dasar
    $clientId   = $gatewayConfig['client_id'] ?? null;
    $secretKey  = $gatewayConfig['secret_key'] ?? null;
    $baseUrl    = $gatewayConfig['base_url'] ?? null;
    $requestPath = $methodConfig['endpoint'] ?? null;

    if (!$clientId || !$secretKey || !$baseUrl || !$requestPath) {
        return ['error' => 'Doku gateway is not properly configured'];
    }

    $targetUrl = rtrim($baseUrl, '/') . '/' . ltrim($requestPath, '/');

    // Waktu ISO8601
    $requestTimestamp = gmdate("Y-m-d\TH:i:s\Z");

    // Signature Component
    $digest = base64_encode(hash('sha256', json_encode($body), true));
    $signatureComponent = "Client-Id:$clientId\nRequest-Id:{$body['order']['invoice_number']}\nRequest-Timestamp:$requestTimestamp\nRequest-Target:$requestPath\nDigest:$digest";

    // HMAC SHA256 Signature
    $signature = base64_encode(hash_hmac('sha256', $signatureComponent, $secretKey, true));
    $signatureHeader = "HMACSHA256=$signature";

    // Header
    $headers = [
        "Content-Type: application/json",
        "Client-Id: $clientId",
        "Request-Id: {$body['order']['invoice_number']}",
        "Request-Timestamp: $requestTimestamp",
        "Signature: $signatureHeader"
    ];



    $response = sendCurlRequest(
        $targetUrl,
        'POST',
        $headers,
        $transaction,
        null
    );


    if (!isset($response['error'])) {
        return $response['response']??[];
    }
    return $response;
}


