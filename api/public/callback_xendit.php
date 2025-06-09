<?php
// public/callback_xendit.php

require_once __DIR__ . '/../db.php';

// Baca input JSON callback
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Contoh validasi sederhana: cek signature jika diperlukan (Xendit bisa pakai header X-Callback-Token)
// Anda bisa tambah validasi token/secrets di sini

// Ambil external_id dari callback (misal invoice id)
$externalId = $data['id'] ?? $data['external_id'] ?? null;
$status = strtolower($data['status'] ?? '');

if (!$externalId || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Cari payment berdasarkan external_id di tabel transaction_payments
$stmt = $pdo->prepare("SELECT tp.id, tp.transaction_id FROM transaction_payments tp WHERE tp.external_id = ? LIMIT 1");
$stmt->execute([$externalId]);
$payment = $stmt->fetch();

if (!$payment) {
    http_response_code(404);
    echo json_encode(['error' => 'Payment not found']);
    exit;
}

// Update status payment dan transaksi utama
$paidAt = ($status === 'paid' || $status === 'settled') ? date('Y-m-d H:i:s') : null;

$updatePayment = $pdo->prepare("UPDATE transaction_payments SET status = ?, paid_at = ?, gateway_response = JSON_MERGE_PATCH(gateway_response, ?) WHERE id = ?");
$updatePayment->execute([
    $status,
    $paidAt,
    $payload,
    $payment['id']
]);

// Jika status sudah paid/settled, update juga transaksi utama
if ($status === 'paid' || $status === 'settled') {
    $updateTrans = $pdo->prepare("UPDATE transactions SET status = 'paid', updated_at = NOW() WHERE id = ?");
    $updateTrans->execute([$payment['transaction_id']]);
}

http_response_code(200);
echo json_encode(['success' => true]);
