<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Payment Completed';
$pageCSS = 'search.css';
require_once 'head.php';
$Stripe_session = $_GET['session_id'] ?? Null;
?>
<body>
    <!--<php include_once 'payment_email.php'; ?>-->
    <main class="payment_completed_main">
        <div class="container" style="text-align:center;margin-top:5vh;">
            <h2>Payment Complete!</h2>
            <br>
            <h3>Thank you for purchasing. These are the items you bought:</h3>
            <br>
            <!-- Display Items Purchased -->
            <div class="row d-flex justify-content-evenly" id="bought-products"></div>
            <br>
            <h4>
            <?php
            date_default_timezone_set('Asia/Hong_Kong');
            echo "Purchase Date: " . date("j F Y, g:i A");
            echo "<br>";
            echo "Purchase Session: " . $Stripe_session;
            echo "<br>";
            echo $_SESSION['user_id'];
            ?>
            </h4>
            <br>
            <button class="btn btn-info" onclick="window.location.href='library.php';">Go To Your Library</button>
            <!-- Print Button -->
            <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
        </div>
    </main>

    <?php require_once 'footer.php'; ?>
    <script src="src/js/cart.js"></script>
    <script src="src/js/cart_button.js"></script>
    <script src="src/js/payment_completed.js" defer></script>
</body>
</html>