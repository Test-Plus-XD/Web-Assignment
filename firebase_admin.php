<?php
require __DIR__ . '/vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

//$serviceAccountPath = __DIR__ . '/firebase-service-account.json';
$serviceAccountPath = __DIR__ . '/web-assignment-4237d-firebase-adminsdk-fbsvc.json';
// Instantise Firebase with service account
$firebase = (new Factory)->withServiceAccount($serviceAccountPath);
// Create Auth instance
$auth = $firebase->createAuth();
?>