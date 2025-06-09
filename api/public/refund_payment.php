<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../payment.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$websiteId = $data['website_id'] ?? null;
$gatewayCode = $data['payment_gateway'] ?? null;
$externalId = $data['external_id'] ?? null;
$body=$data['body'];
if (!$websiteId || !$gatewayCode || !$externalId || $body) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$response = refundPayment($pdo, $externalId, $data);
echo json_encode($response);
