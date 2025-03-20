<?php 
  // Database Management System 
  $dbms = "mysql";
  // Database host
  $dbhost = "localhost";
  // Database name
  $dbname = "mydb";
  // Database user account
  $dbuser = "mydb_user";
  // Database password
  $dbpassword = "password";
  // Data Source Name - contains information required to connect to the database
  $dsn = "$dbms:host=$dbhost;dbname=$dbname;charset=utf8"; 
  // Try to create a PDO connection
  try {
    // Create a new PDO instance
    $conn = new PDO($dsn, $dbuser, $dbpassword);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
    // If the connection fails, show an error message
    echo "Connection failed: " . $e->getMessage();
    exit;
  }
 // Note: With PDO, you do not call a close() method. The connection is automatically closed 
 // When the PDO object is destroyed (or you can explicitly set $conn = null if desired).
 //$conn = null;
?>