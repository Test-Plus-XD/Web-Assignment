<?php
// Connect database
require "Class_db_connect.php";
$tbname = "tb_accounts";

// Session handling
session_start();
$isLoggedIn = isset($_SESSION["isLogin"]) ? $_SESSION["isLogin"] : false;
$_SESSION["login_username"] = "";
$_SESSION["login_message"] = "";

// Define variables from the form
$username = $_POST['username'];
$password = $_POST['password'];
$password_md5 = md5($password);

// Validation: Check for empty fields
if (empty($username) || empty($password)) {
    $_SESSION["login_message"] = "Fields cannot be empty!";
    header("Location: login.php");
    exit;
}

// Prepared statement to prevent SQL injection
$sql = "SELECT user_id, username, password FROM $tbname WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password_md5);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Store login session data
    $_SESSION["isLogin"] = true;
    $_SESSION["username"] = $row["username"];
    $_SESSION["user_id"] = $row["user_id"];
    $_SESSION["login_message"] = "Login successful!";

    // Close the first query statement
    $stmt->close();

    // Fetch owned product count
    $sql = "SELECT COUNT(*) AS product_count FROM tb_owned_products WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $row["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $owned_data = $result->fetch_assoc();
    $library_count = $owned_data["product_count"] ?? 0;

    // Close the statement and database connection
    $stmt->close();
    $conn->close();

    // Pass library count to localStorage using JavaScript
    echo "<script>
        localStorage.setItem('libraryCount', " . json_encode($library_count) . ");
        console.log('Library count updated to:', " . json_encode($library_count) . ");
        window.location.href = 'login.php';
    </script>";
    exit;
} else {
    $_SESSION["login_message"] = "Username or password is incorrect!";
    header("Location: login.php");
    exit;
}
?>