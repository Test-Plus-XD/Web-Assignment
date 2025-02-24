<link rel="stylesheet" href="css/index.css">
<?php
session_start();
echo "Session ID: " . session_id();
echo "<br>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
include_once 'background.php'; 
?>