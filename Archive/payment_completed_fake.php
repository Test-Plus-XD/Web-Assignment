<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Payment Completed';
$pageCSS = 'search.css';
require_once 'head.php';
?>
<body>
    <!--<php include_once 'payment_email.php'; ?>-->
    <main class="payment_completed_main">
        <div class="container" style="text-align:center;margin-top:5vh;">
            <h2>Payment Complete!</h2>
            <br>
            <h3>Thank you for your purchase. <!--Below are the items you bought:--></h3>
            <br>
            <h4>You can check your Library for the items you bought.</h4>
            <br>
            <!-- Display Items Purchased -->
            <div class="row">
                <?php

                ?>
            </div>
            <!-- Print Button -->
            <button class="btn btn-info" onclick="window.location.href='library.php';">Go To Your Library</button>
            <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
        </div>
    </main>

    <?php require_once 'footer.php'; ?>
    <script src="src/js/payment.js" defer></script>
</body>
</html>