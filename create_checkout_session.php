<?php // Not in use
require 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_test_51QQiCZCA0AswCry58ef8rBfji4V8MJjjsSEmBeN9mYJ9Lcsc3mQuyDgZSnptWjlpgSLnbFS6bpK6Lp7UNInN83NZ00PIQlSaTy');

// Parse raw JSON POST body
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Assume $data['items'] = [{ name: "Game A", price: 1999, quantity: 1 }, ...]
$lineItems = [];
foreach ($data['items'] as $item) {
    $lineItems[] = [
        'price_data' => [
            'currency' => 'hkd',
            'product_data' => ['name' => $item['name']],
            'unit_amount' => $item['price'], // e.g., 1999 = HK$19.99
        ],
        'quantity' => $item['quantity'],
    ];
}

$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => $lineItems,
    'mode' => 'payment',
    'success_url' => 'http://localhost/Web%20Assignment/payment_success.php',
    'cancel_url' => 'http://localhost/Web%20Assignment/payment_cancelled.php',
]);

echo json_encode(['id' => $session->id]);