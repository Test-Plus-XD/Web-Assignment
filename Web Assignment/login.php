<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Login';
$pageCSS = 'login.css';
require_once 'head.php';
ini_set('display_errors', 0);
?>

<?php
    // Session handling
    if (session_status() === PHP_SESSION_NONE){
        session_start(); 
    }

    $isLoggedIn = false;
    $isAdmin = false;
    $_login_username = "";
    $_login_message = "";

    if (isset($_SESSION["isLogin"])){
        $isLoggedIn = $_SESSION["isLogin"];
        $isAdmin = $_SESSION["$isAdmin"];
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

<body>
    <main class="login_main">
        <form action="Class_account.php" method="post" onsubmit="setLoginState()" class="form_login">
            <div class="imgcontainer">
                <img src="Multimedia/login.png" alt="Login" class="login">
            </div>

            <div class="container">

                <div class="form-floating">
                    <input type="text" placeholder="" class="form-control" name="username">
                    <label for="username"><b>Enter Username</b></label>
                </div>

                <div class="form-floating position-relative">
                    <input type="password" placeholder="" class="form-control" name="password" id="password">
                    <label for="password"><b>Enter Password</b></label>
                </div>

                <label><input type="checkbox" checked="checked" name="remember"> Remember me</label>

                <span class="psw">Forget <a href="https://help.steampowered.com/en/?snr=1_44_44_">password?</a></span>

                <button type="submit" class="btn btn-primary" value="Login">Login</button>
                <button type="button" class="btn btn-info" value="Register" onclick="window.location.href='registration.php';">Register</button>
                <button type="button" class="cancelbtn btn btn-danger" onclick="history.back()">Cancel</button>
            </div>
        </form>

        <?php
            // Display login message if available.
            echo "<div class='display-5 text-danger m-1' style='text-align:center;'>$_login_message</div>";
        ?>
    </main>

    <script>
        function setLoginState() {
            // Store the login state in localStorage
            localStorage.setItem('isLoggedIn', 'true');
        }
    </script>

    <?php require_once 'footer.php'; ?>
</body>
</html>
<!--#NoEnv  ; Recommended for performance and compatibility with future AutoHotkey releases.
; #Warn  ; Enable warnings to assist with detecting common errors.
SendMode Input  ; Recommended for new scripts due to its superior speed and reliability.
SetWorkingDir %A_ScriptDir%  ; Ensures a consistent starting directory.-->
