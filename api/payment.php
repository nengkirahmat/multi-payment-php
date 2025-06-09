<?php
// payment.php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/gateways/midtrans.php';
require_once __DIR__ . '/gateways/xendit.php';
require_once __DIR__ . '/gateways/tripay.php';
require_once __DIR__ . '/gateways/doku.php';
require_once __DIR__ . '/gateways/stripe.php';
require_once __DIR__ . '/gateways/faspay.php';

function createPayment(PDO $pdo,  array $input): array
{
    $websiteId = (int)($input['website_id']);
    $gatewayCode = strtolower(trim($input['payment_gateway']));
    $paymentMethodCode = strtolower(trim($input['payment_method']));
    $transactionData = $input['body'];
    // Ambil data gateway dari DB
    $gateway = getPaymentConfig($pdo, $websiteId, $gatewayCode);
    // $configGateway = json_decode($gateway['config'], true);
    if (!$gateway) {
        return ['error' => "Gateway '{$gatewayCode}' not found"];
    }

    // Ambil config method dari DB
    $method = getMethodConfig($pdo, $gateway['id'], $paymentMethodCode);
    if (!$method) {
        return ['error' => "Method '{$gatewayCode}' not found"];
    }
    // $configMethod = json_decode($method['config'], true);

    $callbackUrl = $input['callback_url'] ?? null;

    if (!$callbackUrl) {
        return ['error' => "Your Callback Url required"];
    }

    if (!preg_match('/^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{2,63}\b([-a-zA-Z0-9()@:%_\+.~#?&\/=]*)$/', $callbackUrl)) {
        return ['error' => "Invalid your callback URL"];
    }

    // Simpan transaksi awal di DB
    $invoiceCode = $transactionData['invoice_code'] ?? 'INV' . time();
    // $transactionId = saveTransaction($pdo, [
    //     'website_id' => $websiteId,
    //     'payment_gateway_code' => $gatewayCode,
    //     'payment_method_code' => $paymentMethodCode,
    //     'metadata' => $input ?? [],
    // ]);


    // Panggil API gateway yang sesuai
    if ($gatewayCode === 'xendit') {
        $response = xenditCreate($gateway, $method, $transactionData);
    } elseif ($gatewayCode === 'midtrans') {
        $response = midtransCreate($gateway, $method, $transactionData);
    } elseif ($gatewayCode === 'tripay') {
        $response = tripayCreate($gateway, $method, $transactionData);
    } elseif ($gatewayCode === 'doku') {
        $response = dokuCreate($gateway, $method, $transactionData);
    } elseif ($gatewayCode === 'stripe') {
        $response = stripeCreate($gateway, $method, $transactionData);
    } elseif ($gatewayCode === 'faspay') {
        $response = faspayCreate($gateway, $method, $transactionData);
    } else {
        return ['error' => 'Unsupported payment gateway'];
    }

    // Simpan response gateway ke tabel transaction_payments jika sukses
    if (isset($response['error'])) {
        return $response;
    }

    $externalId = $response['id'] ?? ($response['transaction_id'] ?? $invoiceCode);

    // saveTransactionPayment($pdo, [
    //     'transaction_id' => $transactionId,
    //     'external_id' => $externalId,
    //     'gateway_response' => $response,
    //     'status' => $response['status'] ?? 'pending',
    //     'paid_at' => $response['paid_at'] ?? null,
    //     'expired_at' => $response['expiry_date'] ?? null,
    //     'callback_url' => $callbackUrl,
    // ]);

    return [
        'success' => true,
        // 'transaction_id' => $transactionId,
        'payment_gateway_code' => $gatewayCode,
        'payment_method_code' => $paymentMethodCode,
        'gateway_response' => $response,
    ];
}


function refundPayment(PDO $pdo, string $externalId, array $data): array
{
    $websiteId = $data['website_id'] ?? null;
    $gatewayCode = $data['payment_gateway'] ?? null;
    $config = getPaymentConfig($pdo, $websiteId, $gatewayCode);
    if (!$config) {
        return ['error' => "Config for gateway '{$gatewayCode}' not found"];
    }

    if ($gatewayCode === 'xendit') {
        return xenditRefundPayment($config, $externalId, $data);
    } elseif ($gatewayCode === 'midtrans') {
        // return midtransRefundPayment($config, $data);
    }

    return ['error' => 'Unsupported payment gateway'];
}

function cancelInvoice(PDO $pdo, int $websiteId, string $gatewayCode, string $externalId): array
{
    $gateway = getPaymentConfig($pdo, $websiteId, $gatewayCode);
    $config = json_decode($gateway['config'], true);
    if (!$config) {
        return ['error' => "Config for gateway '{$gatewayCode}' not found"];
    }

    if ($gatewayCode === 'xendit') {
        // contoh request
        // {
        //     "website_id": 1,
        //     "payment_gateway": "xendit", 
        //     "external_id": "684264dcc2efed7cfb6bccbd"
        //     }
        $response = xenditCancelPayment($config, $externalId);
        if (isset($response['error_code'])) {
            return $response;
        }
        if (isset($response['id'])) {
            updateCancelInvoice($pdo, $response['id'], $response);
        }
        return $response;
    } elseif ($gatewayCode === 'midtrans') {
        return midtransCancelPayment($config, $externalId);
    }

    return ['error' => 'Unsupported payment gateway'];
}
