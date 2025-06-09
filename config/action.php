<?php
require_once '../api/db.php';

$action = $_POST['action'] ?? '';
$response = ['success' => false];

if ($action == 'create' || $action == 'update') {
    $id = $_POST['id'] ?? null;
    $website_id = $_POST['website_id'] ?? null;
    $code = $_POST['payment_gateway_code'] ?? '';
    $config = $_POST['config'] ?? '';
    $is_active = $_POST['is_active'] ?? 1;

    if (!$website_id || !$code || !$config) {
        $response['message'] = 'All fields are required';
        echo json_encode($response); exit;
    }

    if ($action == 'create') {
        // Unik check
        $stmt = $pdo->prepare("SELECT id FROM payment_gateways WHERE website_id = ? AND payment_gateway_code = ?");
        $stmt->execute([$website_id, $code]);
        if ($stmt->fetch()) {
            $response['message'] = 'Already exists!';
            echo json_encode($response); exit;
        }

        $stmt = $pdo->prepare("INSERT INTO payment_gateways (website_id, payment_gateway_code, config, is_active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$website_id, $code, $config, $is_active]);
    } else {
        $stmt = $pdo->prepare("UPDATE payment_gateway SET config=?, is_active=? WHERE id=?");
        $stmt->execute([$config, $is_active, $id]);
    }

    $response['success'] = true;
} elseif ($action == 'get') {
    $stmt = $pdo->prepare("SELECT * FROM payment_gateways WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $response = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($action == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM payment_gateways WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $response['success'] = true;
}

echo json_encode($response);
