<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Account';
$pageCSS = 'account.css';
include 'head.php';
?>
<script src="js/account_management.js" defer></script>
<body style="font-family:Zen Maru Gothic">
    
    

    <main class="account_main">
        <div class="container" id="account_container">
            <div class="row d-flex justify-content-evenly">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <img src="Multimedia/login.png" alt="Login" class="login">
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-3 p-2">
                    <button id="logoutButton" class="btn btn-warning">Logout</button>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-3 p-2">
                    <b>Username :</b>
                    <?php echo $_SESSION["username"] ?>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-3 p-2">
                    <button id="changeButton" class="btn btn-warning" onclick="window.location.href='change_password.php';">Change Password</button>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-3 p-2">
                    <b>User ID :</b>
                    <?php echo $_SESSION["user_id"] ?>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-3 p-2">
                    <button id="deleteButton" class="btn btn-danger">Delete Account</button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>