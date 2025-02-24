<?php
session_start();
require "Class_db_connect.php";
header("Content-Type: application/json");

$input = json_decode(file_get_contents('php://input'), true);
$tbname = "tb_owned_products";
$productId = $input['productId'] ?? null;
$userId = $_SESSION["user_id"] ?? null;
$session = session_id() ?? null;

if (!$userId || !$productId) {
    echo json_encode(['success' => false, 'message' => 'Missing user or product data']);
    exit;
}

$sql = "INSERT IGNORE INTO $tbname (user_id, product_id, session) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iis', $userId, $productId, $session);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update library']);
}
// Disconnect from the database
$stmt->close();
$conn->close();
?>