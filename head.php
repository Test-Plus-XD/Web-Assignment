<?php
    //$header = 'Content-Type: application/json';
    session_start();
    ini_set('display_errors', 1); // Ensure errors are shown
    ini_set('display_startup_errors', 1);
    ini_set('html_errors', 0);    // Disable HTML formatting of errors
    error_reporting(E_ALL);       // Report all PHP errors
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);// Report all SQL errors
    
    require_once 'vendor/autoload.php';
    \Sentry\init([
      'dsn' => 'https://15a42c46abc6f184fcd2810733380450@o4509003279499264.ingest.de.sentry.io/4509019227619408',
      // Specify a fixed sample rate
      'traces_sample_rate' => 1.0, ]);
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--Third Parties-->
        <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Edu+AU+VIC+WA+NT+Guides:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Honk&display=swap" rel="stylesheet">
        <!-- Tailwindcss 
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> -->
        <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://js-de.sentry-cdn.com/a390895a61d9ae66ff37883b6cf73c6b.min.js" crossorigin="anonymous"></script>
        <!-- jQuery -->
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <!-- reCAPTCHA +&hl=zh_tw -->
    <script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>
        <!-- Firebase -->
    <script src="https://www.gstatic.com/firebasejs/11.5.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.5.0/firebase-app-check-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.5.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.5.0/firebase-analytics-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.5.0/firebase-firestore-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/ui/6.1.0/firebase-ui-auth.js"></script>
    <script src="https://www.gstatic.com/firebasejs/ui/6.1.0/firebase-ui-auth__zh_tw.js"></script>
    <link type="text/css" rel="stylesheet" href="https://www.gstatic.com/firebasejs/ui/6.1.0/firebase-ui-auth.css"/>

    <!--Custom-->
    <title><?php echo $pageTitle ?? 'Title Unavailable'; ?></title>
    <link rel="icon" type="image/x-icon" href="Multimedia/Sliver_Wolf.png">
    <script> const isLoggedIn = <?php echo json_encode($_SESSION["isLogin"] ?? false); ?>; </script>
    <script> const isAdmin = <?php echo json_encode($_SESSION["isAdmin"] ?? false); ?>; </script>
    <script src="dist/bundle.js"></script>
    <script src="src/js/index.js" defer></script>
    <link rel="stylesheet" href="src/css/index.css">
    <link rel="stylesheet" href="src/css/<?php echo $pageCSS ?>">
    <?php require_once 'background.php'; ?>
    <?php require_once 'header.php'; ?>
</head>