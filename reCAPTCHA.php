<?php
// Set the Content-Type header to JSON.
header('Content-Type: application/json');
// Your reCAPTCHA secret key
$secretKey = '6Left_4qAAAAAJoPcX2VF4aAZbQhVlJDLv8A9YJZ';
// Retrieve the token from the POST data.
$token = $_POST['g-recaptcha-response'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No reCAPTCHA token provided."]);
    exit;
}

// Build the URL for verification.
$verifyUrl = "https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($secretKey) . "&response=" . urlencode($token);

// Send the request to Google's verification endpoint.
// Consider using curl for more robust error handling
$verifyResponse = @file_get_contents($verifyUrl);

if ($verifyResponse === false) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "error" => "Failed to connect to reCAPTCHA server."]);
    exit;
}

$verificationData = json_decode($verifyResponse, true);

// For debugging, you can also include your debug token.
$debugToken = '913A6B2B-FDCB-464A-B69E-BFEF50736A2C';

// Check if the verification succeeded with an acceptable score and matching action.
if ($verificationData["success"] &&
    isset($verificationData["score"]) && $verificationData["score"] >= 0.05 && // Increased score threshold for better security
    isset($verificationData["action"]) && $verificationData["action"] === "login") 
    {
    echo json_encode(["success" => true, "message" => "reCAPTCHA verification passed.", "debugToken" => $debugToken]);
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "reCAPTCHA verification failed.", "details" => $verificationData]);
}
?>