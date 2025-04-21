<?php
$mysqli = new mysqli("localhost", "root", "", "mydb");
if ($mysqli->connect_error) {
    die("MySQL connection failed: " . $mysqli->connect_error);
}

$projectID = "web-assignment-4237d";
$apiKey = "AIzaSyCQDJLKGSEzBn3HMqe7c3KHp1iUapZOYm4";

$result = $mysqli->query("SELECT * FROM tb_accounts");
if (!$result || $result->num_rows === 0) {
    die("No rows found in tb_accounts");
}

while ($row = $result->fetch_assoc()) {
    $documentData = [];

    // Sample mapping – assumes 'id' is your MySQL custom test ID
    foreach ($row as $column => $value) {
        if ($value === null) {
            continue;
        } elseif (is_numeric($value)) {
            $documentData[$column] = ["integerValue" => (int)$value];
        } else {
            $utf8Value = mb_convert_encoding($value, 'UTF-8', 'auto');
            $documentData[$column] = ["stringValue" => $utf8Value];
        }
    }

    // REST API insert
    $url = "https://firestore.googleapis.com/v1/projects/$projectID/databases/web-development/documents/tb_accounts?key=$apiKey";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json; charset=UTF-8"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["fields" => $documentData], JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    echo "Response:\n$response\n\n";

    curl_close($ch);
}

$mysqli->close();
?>