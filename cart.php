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
                <div id="cart-items" style="display: flex; justify-content: center;">
                    <!-- Dynamic content will be rendered here -->
                </div>
                <div id="cart-total" style="width=100%; display: flex; justify-content: center;"></div>
                <button id="checkoutButton" class="btn btn-info" style="width=100%">Proceed to Payment</button>
            </div>
        </div>  
    </main>
    <?php require_once 'footer.php'; ?>
    <script src="src/js/cart.js" defer></script>
</body>
</html>