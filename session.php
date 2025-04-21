<link rel="stylesheet" href="src/css/index.css">
<?php
session_start();
echo "Session ID: " . session_id();
echo "<br>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
echo "<br>";
echo "<pre>" . print_r($_SESSION["user_id"]) . "</pre>";
include_once 'background.php'; 

$ch = curl_init('https://www.google.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$output = curl_exec($ch);

if ($output === false) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    echo 'Curl success!';
}
curl_close($ch);
?>