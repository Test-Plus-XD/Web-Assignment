<?php
// Connect database
require "Class_db_connect.php";
$tbname = "tb_accounts";

//Session handling
session_start();
$isLoggedIn = isset($_SESSION["isLogin"]) ? $_SESSION["isLogin"] : false; // Check if user is logged in
$_login_username = $_SESSION["login_username"] = "";
$_login_message = $_SESSION["login_message"] = "";
//var_dump($_SESSION);

/// Define variables from the form
$fullname=$_POST["fullname"];
$username=$_POST["username"];
$password1=$_POST["password1"];
$password2=$_POST["password2"];
$password_md5=md5($password1);
$IsValidated = true;

// Validation check: empty values
if ( empty($fullname) || empty($username) || empty($password1) || empty($password2) ) {    
    $_SESSION["login_message"] = "Fields cannot be empty!";
    $IsValidated = false; // Fail validation
}

// Validation check: passwords not matching
if ($password1 !== $password2) {
    $_SESSION["login_message"] = "Passwords do not match!";
    $IsValidated = false; // Fail validation
}

// SQL: Check if username exists
$sql = "SELECT * FROM $tbname WHERE username='$username';";
$sql_result = $conn->query($sql);

if ($sql_result->num_rows > 0) { 
    $_SESSION["login_message"] = "Username already exists!";
    $IsValidated = false; // Fail validation
}

// Proceed with insertion only if all validations pass
if ($IsValidated) {
    $sql = "INSERT INTO $tbname (fullname, username, password) VALUES ('$fullname', '$username', '$password_md5')";
    $sql_result = $conn->query($sql);
    $last_id = $conn->insert_id;

    if ($sql_result === TRUE) {     
        $_SESSION["login_message"] = "User account ($username) created! Your ID is: ($last_id)";
    } else {
        $_SESSION["login_message"] = "User account creation failed!";
    }
}
// Disconnect database
$conn->close();
header("Location: registration.php"); // Redirect back to the registration page
?>