<?php
// Handles GET requests to fetch IP threat data via ipdata.co
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(403); // Forbidden for non-GET
    echo json_encode([
        "success" => false,
        "error" => "Only GET requests are allowed"
    ]);
    exit;
}

// IPData API key
$ipdataAPIKey = "21c760fcc1d30f404c6b962dfed0b52aa8b51875d4825e2d2b3b3011";

// Detect the IP of the requester
$clientIP = $_SERVER['REMOTE_ADDR'];

// Prepare URL to query IPData with client IP
$url = "https://api.ipdata.co/?api-key={$ipdataAPIKey}";

// Fetch threat data from ipdata.co
$response = file_get_contents($url);

if ($response === false) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Failed to contact ipdata API"
    ]);
    exit;
}

$data = json_decode($response, true);
$threat = $data['threat'] ?? [];

// Determine if the IP is suspicious
$suspicious = (
    !empty($threat['is_tor']) ||
    !empty($threat['is_vpn']) ||
    !empty($threat['is_proxy']) ||
    !empty($threat['is_anonymous']) ||
    !empty($threat['is_known_attacker']) ||
    !empty($threat['is_known_abuser']) ||
    !empty($threat['is_bogon']) ||
    !empty($threat['is_threat'])
);

// Respond with the analysis
echo json_encode([
    "success" => true,
    "IP" => $clientIP,
    "suspicious" => $suspicious,
    "details" => $threat,
    "raw_data" => $data
]);