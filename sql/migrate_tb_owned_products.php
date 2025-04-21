<?php
$mysqli = new mysqli("localhost", "root", "", "mydb");
if ($mysqli->connect_error) {
    die("MySQL connection failed: " . $mysqli->connect_error);
}

$projectID = "web-assignment-4237d";
$apiKey = "AIzaSyCQDJLKGSEzBn3HMqe7c3KHp1iUapZOYm4";

// Load the owned products table
$result = $mysqli->query("SELECT * FROM tb_owned_products");
if (!$result || $result->num_rows === 0) {
    die("No rows found in tb_owned_products");
}

while ($row = $result->fetch_assoc()) {
    // Get Firestore doc ID references (assumes you know their format or stored them elsewhere)
    $accountID = $row['account_id']; // e.g. Firebase Auth UID
    $productID = $row['product_id']; // Firestore-generated product doc ID

    $documentData = [
        "account" => [
            "referenceValue" => "projects/$projectID/databases/web-development/documents/tb_accounts/$accountID"
        ],
        "product" => [
            "referenceValue" => "projects/$projectID/databases/web-development/documents/tb_products/$productID"
        ]
    ];

    // Optional session (can be null)
    if (!empty($row['session'])) {
        $documentData['session'] = ["stringValue" => mb_convert_encoding($row['session'], 'UTF-8', 'auto')];
    }

    $url = "https://firestore.googleapis.com/v1/projects/$projectID/databases/web-development/documents/tb_owned_products?key=$apiKey";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["fields" => $documentData], JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    echo "Response:\n$response\n\n";

    curl_close($ch);
}

$mysqli->close();
?>