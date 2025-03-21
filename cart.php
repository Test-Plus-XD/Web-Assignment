<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Your Cart';
$pageCSS = 'cart.css';
require_once 'head.php';

if (!isset($_SESSION["isLogin"]) || $_SESSION["isLogin"] !== true) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}
?>
<body>
    <main class="cart_main">
        <div class="cart-container container">  
            <h2>Your Cart</h2>  
            <div class="row d-flex justify-content-evenly">
                <div id="cart-items">
                    <!-- Dynamic content will be rendered here -->
                </div>
                <button type="button" class="btn btn-info" onclick="window.location.href='payment.php';">Pay Now</button>
            </div>
        </div>  
    </main>

    <?php require_once 'footer.php'; ?>
    <script src="src/js/cart.js"></script>
</body>
</html>