<?php
/**
 * Membuat transaksi pembayaran menggunakan Stripe.
 *
 * PERHATIAN: Fungsi ini TIDAK menggunakan sendCurlRequest() global karena
 * Stripe memerlukan format payload 'x-www-form-urlencoded', sementara
 * sendCurlRequest() hanya mengirim 'application/json'.
 * Fungsi ini memiliki implementasi cURL internalnya sendiri yang sudah benar.
 *
 * @param array $gateway Berisi config gateway Stripe dari DB.
 * @param array $method Berisi config method Stripe dari DB.
 * @param array $transactionData Payload murni dari user, sesuai dokumentasi Stripe.
 * @return array Response dari API Stripe.
 */
function stripeCreate(array $gateway, array $method, array $transactionData): array
{
    $gatewayConfig = json_decode($gateway['config'] ?? null, true);
    $secretKey = $gatewayConfig['secret_key'] ?? null;
    if (!$secretKey) {
        return ['error' => 'Stripe Secret Key not configured'];
    }

    $baseUrl = $gatewayConfig['base_url'] ?? 'https://api.stripe.com/v1';
    
    $methodConfig = json_decode($method['config'] ?? null, true);
    $endpoint = $methodConfig['endpoint'] ?? '/payment_intents';
    
    $fullUrl = $baseUrl . $endpoint;

    // --- Implementasi cURL Khusus untuk Stripe ---
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $fullUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        // Kirim payload sebagai form-urlencoded, BUKAN JSON
        CURLOPT_POSTFIELDS => http_build_query($transactionData), 
        CURLOPT_HTTPHEADER => [
            // Gunakan Bearer token untuk otentikasi
            "Authorization: Bearer " . $secretKey, 
            // Tentukan content type yang benar
            "Content-Type: application/x-www-form-urlencoded"
        ],
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['error' => 'Curl error for Stripe: ' . $err];
    }

    $result = json_decode($response, true);
    return $result ?: ['error' => 'Invalid JSON response from Stripe'];
}