<?php
// public/callback_midtrans.php

require_once __DIR__ . '/../db.php';

// Baca input JSON callback
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Validasi signature_key (Midtrans mengirim signature_key)
// Signature key validasi untuk keamanan callback

// $serverKey = ''; // Ambil server key dari config DB sesuai gateway midtrans, website_id dsb

// Untuk demo, misal ambil server key dari DB hardcode 1, atau buat fungsi ambil config, contoh: 
$stmtKey = $pdo->prepare("SELECT config FROM payment_gateways WHERE payment_gateway_code = 'midtrans' AND is_active = 1 LIMIT 1");
$stmtKey->execute();
$row = $stmtKey->fetch();
if (!$row) {
    http_response_code(500);
    echo json_encode(['error' => 'Server key config not found']);
    exit;
}
$config = json_decode($row['config'], true);
$serverKey = $config['server_key'] ?? '';

$signatureKey = $data['signature_key'] ?? '';
$orderId = $data['order_id'] ?? '';
$statusCode = $data['status_code'] ?? '';
$grossAmount = $data['gross_amount'] ?? '';

if (!$signatureKey || !$orderId || !$statusCode || !$grossAmount) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Buat signature key yang benar
$input = $orderId . $statusCode . $grossAmount . $serverKey;
$expectedSignature = hash('sha512', $input);

if ($signatureKey !== $expectedSignature) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Cari payment berdasarkan external_id = order_id
$stmt = $pdo->prepare("SELECT tp.id, tp.transaction_id FROM transaction_payments tp WHERE tp.external_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$payment = $stmt->fetch();

if (!$payment) {
    http_response_code(404);
    echo json_encode(['error' => 'Payment not found']);
    exit;
}

// Update status payment dan transaksi utama
$transactionStatus = strtolower($data['transaction_status'] ?? '');
$fraudStatus = strtolower($data['fraud_status'] ?? '');
$paidAt = null;

if ($transactionStatus === 'capture' && $fraudStatus === 'accept') {
    $paidAt = date('Y-m-d H:i:s');
} elseif ($transactionStatus === 'settlement') {
    $paidAt = date('Y-m-d H:i:s');
}

$updatePayment = $pdo->prepare("UPDATE transaction_payments SET status = ?, paid_at = ?, gateway_response = JSON_MERGE_PATCH(gateway_response, ?) WHERE id = ?");
$updatePayment->execute([
    $transactionStatus,
    $paidAt,
    $payload,
    $payment['id']
]);

// Jika status sudah paid, update transaksi utama
if ($paidAt) {
    $updateTrans = $pdo->prepare("UPDATE transactions SET status = 'paid', updated_at = NOW() WHERE id = ?");
    $updateTrans->execute([$payment['transaction_id']]);
}

http_response_code(200);
echo json_encode(['success' => true]);
