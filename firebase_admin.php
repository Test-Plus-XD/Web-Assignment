<?php
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

$serviceAccountPath = __DIR__ . '/firebase-service-account.json';

$firebase = (new Factory)->withServiceAccount($serviceAccountPath);

$auth = $firebase->createAuth(); // Create Auth instance
?>