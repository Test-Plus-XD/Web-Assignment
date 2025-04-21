<?php
// MySQL connection
$mysqli = new mysqli("localhost", "root", "", "mydb");
if ($mysqli->connect_error) {
    die("MySQL connection failed: " . $mysqli->connect_error);
}

// Firebase config
$projectID = "web-assignment-4237d";
$apiKey = "AIzaSyCQDJLKGSEzBn3HMqe7c3KHp1iUapZOYm4";

// Query products
$result = $mysqli->query("SELECT * FROM tb_products");
if (!$result) {
    die("SQL query failed: " . $mysqli->error);
}
if ($result->num_rows === 0) {
    die("No products found.");
}

while ($row = $result->fetch_assoc()) {
    $documentData = [];

    foreach ($row as $column => $value) {
        if (is_numeric($value)) {
            if (strpos($value, '.') !== false) {
                $documentData[$column] = ["doubleValue" => (float)$value];
            } else {
                $documentData[$column] = ["integerValue" => (int)$value];
            }
        } else {
            // Enforce UTF-8 encoding to avoid Unicode issues
            $utf8Value = mb_convert_encoding($value, 'UTF-8', 'auto');
            $documentData[$column] = ["stringValue" => $utf8Value];
        }
    }

    $url = "https://firestore.googleapis.com/v1/projects/$projectID/databases/web-development/documents/tb_products?key=$apiKey";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json; charset=UTF-8"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["fields" => $documentData], JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ⚠ Only for testing

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch) . "\n";
    } else {
        echo "HTTP $httpCode: $response\n\n";
    }

    curl_close($ch);
}

$mysqli->close();
?>