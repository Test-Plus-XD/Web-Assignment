<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Change Password';
$pageCSS = 'registration.css';
require_once 'head.php';
ini_set('display_errors', 0);
?>

<?php
    // Session handling
    if (session_status() === PHP_SESSION_NONE){
        session_start(); 
    }
    $_login_username = "";
    $_login_message = "";

    if (isset($_SESSION["isLogin"])){
        $isLoggedIn = $_SESSION["isLogin"];
        $_login_username = $_SESSION["login_username"];
        $_login_message = $_SESSION["login_message"];
    }

    if (isset($_SESSION["login_message"])) {
        $_login_message = $_SESSION["login_message"]; // Store the message
        $_SESSION["login_message"] = ""; // Clear it after displaying
    } else {
        $_login_message = "";
    }
?>

<body style="">
    <main>
    <!--action="action_page.php"-->
    <form action="Class_account.php" method="post" onsubmit="setLoginState()" class="form_login">
        <div class="container">
            <h1>Change Password</h1>
            <hr>
            <div class="form-floating">
                <input type="password" placeholder="" class="form-control" name="password_check" id="password_check" required>
                <label for="password_check"><b>Enter Current Password</b></label>
            </div>

            <div class="form-floating">
                <input type="password" placeholder="" class="form-control" name="password1" id="password1" required>
                <label for="password1"><b>New Password</b></label>
            </div>

            <div class="form-floating">
                <input type="password" placeholder="" class="form-control" name="password2" id="password2" required>
                <label for="password2"><b>Repeat New Password</b></label>
            </div>
            <hr>
            <button type="submit" class="registerbtn">Confirm</button>
            <?php
                // Return message
                echo "<div class='display-5 text-danger m-1' style='text-align:center;'>$_login_message</div>";
            ?>
        </div>
    </form>
    </main>

    <?php require_once 'footer.php'; ?>
</body>
</html>