<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Payment';
$pageCSS = 'payment.css';
require_once 'head.php';
?>
<body>
    <main class="payment_main">
        <div class="container" style="text-align:center;margin-bottom:10vh" id="payment_container">
            <h1>Payment</h1>
            <div class="confirmbox" style="margin-bottom:5vh">
                <a onclick="history.go(-1)"> Return to previous page </a>
            </div>

            <div class="container" style="text-align:left" id="payment_container">
                <form action="payment_completed.php" method="POST">
                    <div class="row">
                        <div class="col">
                            <h3 class="title">Billing Address</h3>
                            <div class="inputBox">
                                <label for="name">Full Name:</label>
                                <input type="text" id="name" name="name" placeholder="Enter your full name" require>
                            </div>
                            <div class="inputBox">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" placeholder="Enter email address" require>
                            </div>
                            <div class="inputBox">
                                <label for="address">Address:</label>
                                <input type="text" id="address" name="address" placeholder="Enter address" require>
                            </div>
                            <div class="inputBox">
                                <label for="city">City:</label>
                               <input type="text" id="city" name="city" placeholder="Enter city" require>
                            </div>
                            <div class="inputBox">
                                <select name="State" id="State">
                                    <option value="new territories">New Territories</option>
                                    <option value="Kowloon">Kowloon</option>
                                    <option value="HK Island">Hong Kong Island</option>
                                </select>
                            </div>
                            <div class="inputBox">
                                <label for="zip">Zip Code:</label>
                                <input type="number" id="zip" name="zip" placeholder="123456">
                            </div>
                        </div>
                        <div class="col">
                            <h3 class="title">Payment</h3>
                            <div class="inputBox">
                                <label for="cardName">Name on Card:</label>
                                <input type="text" id="cardName" name="cardName" placeholder="Enter card owner name" require>
                            </div>
                            <div class="inputBox">
                                <label for="cardNum">Card Number:</label>
                                <input type="text" id="cardNum" name="cardNum" placeholder="1111-2222-3333-4444" require>
                            </div>
                            <div class="inputBox">
                                <label for="expMonth">Exp Month:</label>
                                <input type="text" id="expMonth" name="expMonth" placeholder="September" require>
                            </div>
                            <div class="flex">
                                <div class="inputBox">
                                    <label for="expYear">Exp Year:</label>
                                    <input type="text" id="expYear" name="expYear" placeholder="2024" require>
                                </div>
                                <div class="inputBox">
                                    <label for="cvv">CVV:</label>
                                    <input type="text" id="cvv" name="cvv" placeholder="123" require>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="submit" value="Continue to checkout" class="btn">
                </form>
            </div>
        </div>
    </main>

    <?php require_once 'footer.php'; ?>
</body>
</html>