<?php
// public/create_payment.php

header('Content-Type: application/json');

require_once __DIR__ . '/../payment.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$websiteId = (int)($input['website_id'] ?? 0);
$gatewayCode = strtolower(trim($input['payment_gateway'] ?? ''));
$paymentMethodCode = strtolower(trim($input['payment_method'] ?? ''));
$transactionData = $input['body'] ?? [];
if (!$websiteId || !$gatewayCode || !$paymentMethodCode || empty($transactionData)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$result = createPayment($pdo, $input);

if (isset($result['error'])) {
    http_response_code(400);
}

echo json_encode($result);


// XENDIT INVOICE
// {
//     "website_id": 1,
//     "payment_gateway": "xendit", 
//     "payment_method": "invoice", 
//     "callback_url": "https://localhost.com/your_callback",
//     "body": {
//       "external_id": "ORDER-123456",
//       "amount": 150000
//     }
// }

// XENDIT DANA
// {
//     "website_id": 1,
//     "payment_gateway": "xendit", 
//     "payment_method": "DANA", 
//     "callback_url": "https://localhost.com/your_callback",
//     "body": {
//       "reference_id": "order-id-123",
// "currency": "IDR",
// "amount": 25000,
// "checkout_method": "ONE_TIME_PAYMENT",
// "channel_code": "ID_DANA",
// "channel_properties": {
// "success_redirect_url": "https://redirect.me/payment"
// }
//     }
//   }

// XENDIT OVO
// {
//     "website_id": 1,
//     "payment_gateway": "xendit", 
//     "payment_method": "ovo", 
//     "callback_url": "https://localhost.com/your_callback",
//     "body": {
//       "reference_id": "order-id-123",
// "currency": "IDR",
// "amount": 25000,
// "checkout_method": "ONE_TIME_PAYMENT",
// "channel_code": "ID_OVO",
// "channel_properties": {
// "mobile_number": "6289636137032"
// }
//     }
//   }

// XENDIT QRIS
// {
//     "website_id": 1,
//     "payment_gateway": "xendit", 
//     "payment_method": "QRIS", 
//     "callback_url": "https://localhost.com/your_callback",
//     "body": {
//      "external_id": "order-id-166642020",
// "type": "DYNAMIC",
// "currency": "IDR",
// "amount": 10000,
// "callback_url":"https://localhost.com/your_callback"
//     }
//   }


// MIDTRANS INVOICES
// {
//     "website_id": 1,
//     "payment_gateway": "midtrans", 
//     "payment_method": "shopeepay", 
//     "callback_url": "https://localhost.com/your_callback",
//     "body": 
// {
//     "order_id": "INV-20250607-001",
//     "invoice_number": "INV-20250607-001",
//     "invoice_date": "2025-06-07T10:00:00+07:00",
//     "due_date": "2025-06-14T23:59:59+07:00",
//     "customer_details": {
//       "id": "CUST-001",
//       "name": "Budi Santoso",
//       "email": "budi@example.com",
//       "phone": "628123456789"
//     },
//     "item_details": [
//       {
//         "item_id": "SKU-001",
//         "description": "Produk A",
//         "quantity": 2,
//         "price": 50000
//       },
//       {
//         "item_id": "SKU-002",
//         "description": "Produk B",
//         "quantity": 1,
//         "price": 100000
//       }
//     ],
//     "payment_type": "payment_link",
//     "reference": "PO-20250607-001"
//   }
// }

// MIDTRANS PAYMENT_LINK
// {
//     "website_id": 1,
//     "payment_gateway": "midtrans", 
//     "payment_method": "shopeepay", 
//     "callback_url": "https://localhost.com/your_callback",
//     "body": 
// {"transaction_details":{"order_id":"concert-ticket-01","gross_amount":100000},"usage_limit":2}
// }


// MIDTRANS SHOPEEPAY
// {
//     "website_id": 1,
//     "payment_gateway": "midtrans", 
//     "payment_method": "shopeepay", 
//     "callback_url": "https://localhost.com/your_callback",
//     "body": {
// "payment_type": "shopeepay",
// "transaction_details": {
// "order_id": "test-order-shopeepay-001",
// "gross_amount": 25000
// },
// "item_details": [
// {
// "id": "id1",
// "price": 25000,
// "quantity": 1,
// "name": "Brown sugar boba milk tea"
// }
// ],
// "customer_details": {
// "first_name": "John",
// "last_name": "Brandon",
// "email": "john.brandon@go-jek.com",
// "phone": "0819323212312"
// },
// "shopeepay": {
// "callback_url": "https://midtrans.com/"
// }
// }


//   }

//MIDTRANS GOPAY
// {
//     "website_id": 1,
//     "payment_gateway": "midtrans", 
//     "payment_method": "gopay", 
//     "callback_url": "https://localhost.com/your_callback",
//     "body": {
// "payment_type": "gopay",
// "transaction_details": {
// "order_id": "order04",
// "gross_amount": 275000
// },
// "customer_details": {
// "phone": "081223323423"
// },
// "gopay": {
// "enable_callback": true,
// "callback_url": "someapps://callback"
// }
// }
//   }

// TRIPAY QRIS
// {"website_id":"1",
//     "payment_gateway": "tripay", 
//     "payment_method": "qris", 
//     "callback_url": "https://localhost.com/your_callback",
//     "body":{
//         "method": "QRIS",
//         "merchant_ref": "INV20250607-001",
//         "amount": 150000,
//         "customer_name": "Budi Santoso",
//         "customer_email": "budi@example.com",
//         "customer_phone": "081234567890",
//         "order_items": [
//             {
//             "sku": "PROD-001",
//             "name": "Kaos Polos",
//             "price": 75000,
//             "quantity": 2,
//             "product_url": "https://example.com/product/kaos-polos",
//             "image_url": "https://example.com/images/kaos.jpg"
//             }
//         ],
//         "return_url": "https://yourwebsite.com/thank-you",
//         "callback_url": "https://yourapi.com/api/callback/tripay",
//         "expired_time": 1731000000
//         }
//     }
    

// DOKU CHECKOUT
// {
//     "website_id": 1,
//     "payment_gateway": "doku",
//     "payment_method": "checkout",
//     "callback_url": "https://localhost.com/your_callback",
//     "body": {
//       "order": {
//         "invoice_number": "INV-20250606-001",
//         "amount": 150000
//       },
//       "payment": {
//         "payment_due_date": 60,
//         "payment_method_types": ["EMONEY_SHOPEE_PAY","EMONEY_DANA","EMONEY_OVO","EMONEY_LINKAJA","EMONEY_DOKU"]
//       },
//       "customer": {
//         "id": "CUST-01",
//         "name": "John Doe",
//         "email": "john@example.com",
//         "phone": "081234567890"
//       }
//     }
//   }


// Referensi DOKU CHECKOUT
// | Metode      | Code DOKU             |
// | ----------- | --------------------- |
// | QRIS        | `"EMONEY_SHOPEE_PAY"` |
// | DANA        | `"EMONEY_DANA"`       |
// | OVO         | `"EMONEY_OVO"`        |
// | LinkAja     | `"EMONEY_LINKAJA"`    |
// | DOKU Wallet | `"EMONEY_DOKU"`       |
