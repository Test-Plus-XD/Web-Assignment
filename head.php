<?php
    session_start();
    ini_set('display_errors', 1); // Ensure errors are shown
    ini_set('display_startup_errors', 1);
    ini_set('html_errors', 0);    // Disable HTML formatting of errors
    error_reporting(E_ALL);       // Report all PHP errors
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);// Report all SQL errors
    //$header = 'Content-Type: application/json';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Title Unavailable'; ?></title>
    <link rel="icon" type="image/x-icon" href="Multimedia/Sliver_Wolf.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Edu+AU+VIC+WA+NT+Guides:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Honk&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script> const isLoggedIn = <?php echo json_encode($_SESSION["isLogin"] ?? false); ?>; </script>
    <script> const isAdmin = <?php echo json_encode($_SESSION["isAdmin"] ?? false); ?>; </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="js/diagram.js?v=<?php echo time(); ?>"></script>
    <script src="js/index.js" defer></script>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/<?php echo $pageCSS ?>">
    <?php require_once 'background.php'; ?>
    <?php require_once 'header.php'; ?>
</head>