<?php
// Required to store reCAPTCHA result globally across pages
if (session_status() === PHP_SESSION_NONE) session_start();
// Set the Content-Type header to JSON.
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "error" => "Invalid request method. POST required."]);
    exit;
}

// Your secret reCAPTCHA key (from Google Console)
$secretKey = '6Left_4qAAAAAJoPcX2VF4aAZbQhVlJDLv8A9YJZ';

// Get token from POST data
$token = $_POST['g-recaptcha-response'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No reCAPTCHA token provided."]);
    exit;
}

// Construct the API request to Google's reCAPTCHA endpoint
$verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
$postData = http_build_query([
    'secret' => $secretKey,
    'response' => $token
]);

// Use cURL instead of file_get_contents for better control
$ch = curl_init($verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
$verifyResponse = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// If request failed entirely
if ($verifyResponse === false) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Verification request failed.", "curlError" => $curlError]);
    exit;
}

// Decode Google's response
$verificationData = json_decode($verifyResponse, true);

// Optional debug token for testing
$debugToken = '913A6B2B-FDCB-464A-B69E-BFEF50736A2C';

// If reCAPTCHA passed
if (!empty($verificationData['success']) && $verificationData['success'] === true) {
    $_SESSION['recaptcha_verified'] = true; // Save verification globally
    $_SESSION['recaptcha_verified_time'] = time(); // Store current time
    echo json_encode([
        "success" => true,
        "message" => "reCAPTCHA verification passed.",
        "debugToken" => $debugToken,
        "details" => $verificationData
    ]);
} else {
    $_SESSION['recaptcha_verified'] = false; // Explicitly mark failure
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "reCAPTCHA verification failed.",
        "details" => $verificationData
    ]);
}
?>