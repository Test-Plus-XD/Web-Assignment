<?php
//Session handling
session_start();
require 'Class_db_connect.php';
header('Content-Type: application/json');
$tbname = "tb_accounts";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;
     //Logout Account
    if ($action === 'logout') {
        echo json_encode(['success' => true, 'message' => 'Logout successful']);
        session_destroy();
        $_SESSION["isLogin"] = false;
        exit;
     //Delect Account
    } elseif ($action === 'delete') {
        $userId = $_SESSION['user_id'] ?? null;

        if (isset($userId)) {
            $sql = "DELETE FROM $tbname WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $userId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Account deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
            }
            session_destroy();
            $_SESSION["isLogin"] = false;
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'No user logged in']);
        }
        $conn->close();
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>