<?php

/**
 * Membuat transaksi pembayaran menggunakan Faspay.
 * Fungsi ini membuat signature dan menyisipkannya ke dalam payload
 * sebelum dikirim ke API Faspay.
 *
 * @param array $gateway Berisi config gateway Faspay dari DB.
 * @param array $method Berisi config method Faspay dari DB.
 * @param array $transactionData Payload murni dari user, sesuai dokumentasi Faspay.
 * @return array Response dari API Faspay.
 */
function faspayCreate(array $gateway, array $method, array $transactionData): array
{
    $gatewayConfig = json_decode($gateway['config'] ?? null, true);
    $userId = $gatewayConfig['user_id'] ?? null;
    $password = $gatewayConfig['password'] ?? null; // Ini adalah password API Faspay
    $baseUrl = $gatewayConfig['base_url'] ?? null;

    if (!$userId || !$password || !$baseUrl) {
        return ['error' => 'Faspay config (user_id, password, base_url) is incomplete.'];
    }
    
    $methodConfig = json_decode($method['config'] ?? null, true);
    $endpoint = $methodConfig['endpoint'] ?? null;
    if (!$endpoint) {
        return ['error' => 'Faspay API method endpoint not configured'];
    }
    
    $fullUrl = $baseUrl . $endpoint;

    // Ambil 'bill_no' dari payload user untuk membuat signature
    $billNo = $transactionData['bill_no'] ?? null;
    if (!$billNo) {
        return ['error' => "Payload for Faspay must contain a 'bill_no' key."];
    }

    // Buat signature sesuai aturan Faspay
    $signature = sha1(md5($userId . $password . $billNo));

    // Sisipkan signature ke dalam payload yang akan dikirim
    $payloadToSend = $transactionData;
    $payloadToSend['signature'] = $signature;

    // Panggil sendCurlRequest dengan payload yang sudah dimodifikasi
    return sendCurlRequest($fullUrl, 'POST', [], $payloadToSend);
}