<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Registration';
$pageCSS = 'registration.css';
require_once 'head.php';
ini_set('display_errors', 0);
?>

<?php
    // Session handling
    if (session_status() === PHP_SESSION_NONE){
        session_start(); 
    }

    $isLoggedIn = false;
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
 <form action="Class_account.php" method="post">
  <div class="container">
    <h1>Register</h1>
    <p>Please fill in this form to create an account.</p>
    <hr>

    <div class="form-floating">
    <input type="text" placeholder="" class="form-control" name="fullname" id="fullname" required>
    <label for="fullname"><b>Enter Fullname</b></label>
    </div>

    <div class="form-floating">
    <input type="text" placeholder="" class="form-control" name="username" id="username" required>
    <label for="username"><b>Enter Username</b></label>
    </div>

    <div class="form-floating">
    <input type="password" placeholder="" class="form-control" name="password1" id="password1" required>
    <label for="password"><b>Enter Password</b></label>
    </div>

    <div class="form-floating">
    <input type="password" placeholder="" class="form-control" name="password2" id="password2" required>
    <label for="password2"><b>Repeat Password</b></label>
    </div>

    <hr>
    <p>By creating an account you agree to our <a href="#">Terms & Privacy</a>.</p>
    <button type="submit" class="registerbtn">Register</button>
  </div>
  <?php
        // Return message
        echo "<div class='display-5 text-danger m-1' style='text-align:center;'>$_login_message</div>";
    ?>
  <div class="container signin">
    <p>Already have an account? <a href="login.php">Sign in</a>.</p>
  </div>
 </form>

    <?php require_once 'footer.php'; ?>
</body>
</html>