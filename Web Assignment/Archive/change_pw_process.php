<?php
// Connect database
require "Class_db_connect.php";
$tbname = "tb_accounts";

// Session handling
session_start();
$userId = $_SESSION["user_id"];
$_login_message = $_SESSION["login_message"] = "";

// Define variables from the form
$password_check = $_POST["password_check"];
$password1 = $_POST["password1"];
$password2 = $_POST["password2"];
$password_md5 = md5($password1);
$IsValidated = true;

// Validation check: empty values
if (empty($password_check) || empty($password1) || empty($password2)) {    
    $_SESSION["login_message"] = "Fields cannot be empty!";
    $IsValidated = false;
}

// Validation check: passwords not matching
if ($password1 !== $password2) {
    $_SESSION["login_message"] = "Passwords do not match!";
    $IsValidated = false;
}

// Verify current password
if ($IsValidated) {
    $sql = "SELECT password FROM $tbname WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($storedPassword);
    $stmt->fetch();

    if (md5($password_check) !== $storedPassword) {
        $_SESSION["login_message"] = "Current password is incorrect";
        $IsValidated = false;
    }
    $stmt->close(); // Clear resources for the SELECT query
}

// Update password if validation passes
if ($IsValidated) {
    $sql = "UPDATE $tbname SET password = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $password_md5, $userId);

    if ($stmt->execute()) {
        $_SESSION["login_message"] = "Password updated successfully";
    } else {
        $_SESSION["login_message"] = "Failed to update password";
    }
    $stmt->close(); // Clear resources for the UPDATE query
}
$conn->close();
header("Location: change_password.php"); // Redirect back to the change_password page
?>