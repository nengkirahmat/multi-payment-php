<?php
// helpers.php

function getPaymentConfig(PDO $pdo, int $websiteId, string $gateway): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM payment_gateways WHERE website_id = ? AND payment_gateway_code=? AND is_active = 1 LIMIT 1");
    $stmt->execute([$websiteId, $gateway]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return $row;
}

function getMethodConfig(PDO $pdo, int $payment_gateway_id, string $methodCode): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE payment_gateway_id = ? AND code = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$payment_gateway_id, $methodCode]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return $row;
}

function saveTransaction(PDO $pdo, array $data): int
{
    $sql = "INSERT INTO transactions (website_id, invoice_code, customer_name, total_amount, status, payment_gateway_code, payment_method_code, metadata, created_at, updated_at) 
            VALUES (:website_id, :invoice_code, :customer_name, :total_amount, :status, :payment_gateway_code, :payment_method_code, :metadata, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':website_id' => $data['website_id'],
        ':invoice_code' => $data['invoice_code'],
        ':customer_name' => $data['customer_name'] ?? null,
        ':total_amount' => $data['total_amount'],
        ':status' => 'pending',
        ':payment_gateway_code' => $data['payment_gateway_code'],
        ':payment_method_code' => $data['payment_method_code'],
        ':metadata' => json_encode($data['metadata'] ?? []),
    ]);
    return (int)$pdo->lastInsertId();
}

function saveTransactionPayment(PDO $pdo, array $data): int
{
    $sql = "INSERT INTO transaction_payments (transaction_id, external_id, gateway_response, status, paid_at, expired_at,callback_url, created_at, updated_at) 
            VALUES (:transaction_id, :external_id, :gateway_response, :status, :paid_at, :expired_at,:callback_url, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':transaction_id' => $data['transaction_id'],
        ':external_id' => $data['external_id'],
        ':gateway_response' => json_encode($data['gateway_response']),
        ':status' => $data['status'],
        ':paid_at' => $data['paid_at'] ?? null,
        ':expired_at' => $data['expired_at'] ?? null,
        ':callback_url' => $data['callback_url'],
    ]);
    return (int)$pdo->lastInsertId();
}

function updateCancelInvoice(PDO $pdo, string $external_id, array $data): bool
{
    // Cari transaction_id dari transaction_payments berdasarkan external_id
    $stmt = $pdo->prepare("SELECT transaction_id FROM transaction_payments WHERE external_id = :external_id LIMIT 1");
    $stmt->execute([':external_id' => $external_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return false; // External ID tidak ditemukan
    }

    $transaction_id = $result['transaction_id'];
    $status = $data['status'] ?? 'cancelled';

    // Update status pada transactions
    $updateTransaction = $pdo->prepare("UPDATE transactions SET status = :status, updated_at = NOW() WHERE id = :transaction_id");
    $updateTransaction->execute([
        ':status' => $status,
        ':transaction_id' => $transaction_id,
    ]);

    // Update status pada transaction_payments
    $updatePayment = $pdo->prepare("UPDATE transaction_payments SET status = :status, updated_at = NOW() WHERE external_id = :external_id");
    $updatePayment->execute([
        ':status' => $status,
        ':external_id' => $external_id,
    ]);

    return true;
}



function sendCurlRequest(string $url, string $method = 'POST', array $headers = [], array $payload = [], ?string $auth = null): array
{
    $ch = curl_init($url);

    $defaultHeaders = ['Content-Type: application/json'];
    $headers = array_merge($defaultHeaders, $headers);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($auth) {
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
    }

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    } elseif (strtoupper($method) === 'GET') {
        // Untuk GET, tambahkan payload ke URL jika perlu
        if (!empty($payload)) {
            $url .= '?' . http_build_query($payload);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    } elseif (strtoupper($method) === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    } elseif (strtoupper($method) === 'PATCH' || strtoupper($method) === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['error' => 'Curl error: ' . $err];
    }

    $result = json_decode($response, true);
    return $result ?: ['error' => 'Invalid JSON response'];
}


