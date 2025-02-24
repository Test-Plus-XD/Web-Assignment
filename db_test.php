<?php
// include database and object files
include_once 'Class_db_connect.php';

// get database connection
$database = new Database();
$connector = $database->getConnection();
?>