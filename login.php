<!DOCTYPE html>
<html lang="en">
<?php
$pageTitle = 'Login';
$pageCSS = 'login.css';
require_once 'head.php';
$site_key = '6Left_4qAAAAAGSyUGZfW4CPtYlVch3kqI5NWR6X';
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
        $isAdmin = $_SESSION["isAdmin"];
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
        <!-- Original Login Form -->
        <form action="Class_account.php" method="post" id="login-form" class="form_login" onsubmit="return false;">
            <input type="hidden" id="recaptchaToken" name="g-recaptcha-response">
            <div class="imgcontainer">
                <img src="Multimedia/login.png" alt="Login" class="login">
            </div>
            <div class="container">
                <!-- For Firebase email sign-in, treat this as the email field -->
                <!--<input type="email" placeholder="Email" class="form-control" name="email" id="loginEmail" required autocomplete="email">-->
                <!--<label for="loginEmail"><b>Enter Email</b></label>-->
                <div class="form-floating position-relative">
                    <input type="text" placeholder="" class="form-control" name="username" id="username" required autocomplete="username">
                    <label for="username"><b>Enter Username</b></label>
                </div>

                <div class="form-floating position-relative">
                    <input type="password" placeholder="Password" class="form-control" name="password" id="loginPassword" required autocomplete="current-password">
                    <label for="loginPassword"><b>Enter Password</b></label>
                </div>

                <!-- Hidden field to store reCAPTCHA token -->
                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
                <button type="submit" class="btn btn-primary" value="Login" onclick="executeRecaptcha('login', submitLoginForm)">Login</button>

                <!-- Trigger Firebase sign-in -->
                <!--<button type="button" id="email-signin-btn" class="btn btn-primary" onclick="executeRecaptcha('verify')">Login with Email</button>-->
                <button type="button" class="btn btn-info" onclick="window.location.href='registration.php';">Register</button>
                <button type="button" class="cancelbtn btn btn-danger" onclick="history.back()">Cancel</button>

                <!-- Divider -->
                <hr style="margin: 30px 3;">

                <!-- Social login options -->
                <div class="social-login" style="text-align: center;">
                    <h3>Or sign in with:</h3>
                    <!-- Email Sign-In -->
                    <button id="email-signin-btn" class="btn btn-warning" data-sitekey="<?php echo $site_key ?>" onclick="executeRecaptcha('email_signin', handleEmailSignIn)">
                        <i class="bi bi-mailbox2-flag"></i> Email
                    </button>
                    <!-- Google Sign-In -->
                    <button id="google-signin-btn" class="btn btn-danger" data-sitekey="<?php echo $site_key ?>" onclick="executeRecaptcha('google_signin', handleGoogleSignIn)">
                        <i class="bi bi-google"></i> Google
                    </button>
                    <!-- GitHub Sign-In -->
                    <button id="github-signin-btn" class="btn btn-dark" data-sitekey="<?php echo $site_key ?>" onclick="executeRecaptcha('github_signin', handleGitHubSignIn)">
                        <i class="bi bi-github"></i> GitHub
                    </button>
                    <!-- Anonymous Sign-In -->
                    <button id="anonymous-signin-btn" class="btn btn-secondary" data-sitekey="<?php echo $site_key ?>" onclick="executeRecaptcha('anonymous_signin', handleAnonymousSignIn)">
                        <i class="bi bi-person-walking"></i> Anonymous
                    </button>
                </div>

                <!-- reCAPTCHA UI -->
                <!-- <div class="g-recaptcha" style="display: flex; justify-content: center; align-items: center;" data-sitekey="<?php echo $site_key ?>"></div> -->
                <!-- Fallback for non-JS users -->
                <noscript>
                    <div style="width: 302px; height: 422px;">
                        <div style="width: 302px; height: 422px; position: relative;">
                            <div style="width: 302px; height: 422px; position: absolute;">
                                <iframe
                                    src="https://www.google.com/recaptcha/api/fallback?k=<?php echo $site_key ?>"
                                    frameborder="0"
                                    scrolling="no"
                                    style="width: 302px; height:422px; border-style: none;"
                                ></iframe>
                            </div>
                            <div style="width: 250px; height: 80px; position: absolute; border-bottom: 1px solid #d3d3d3; background: #f9f9f9; color: #dadada; font-family: Arial, sans-serif; font-size: 12px; padding: 10px; text-align: center; left: 15px; top: 345px;">
                                <span style="width: 250px; height: 80px; display: inline-block;"></span>
                            </div>
                        </div>
                    </div>
                </noscript>

                <!-- FirebaseUI container for authentication widget -->
                <div style="text-align:center;">Firebase UI</div>
                <div id="firebaseui-auth-container"></div>
                <div id="loader"> Loading.... </div>
            </div>
        </form>
        <?php
            // Display login message if available.
            echo "<div class='display-5 text-danger m-1' style='text-align:center;'>$_login_message</div>";
        ?>
    </main>
    <!-- Attaches event listeners to the buttons -->
    <script src="src/js/firebase_cdn.js"></script>
    <script src="src/js/firebaseUI.js"></script>
    <script src="src/js/reCAPTCHA.js"></script>
    <script src="src/js/login.js" defer></script>
    <?php require_once 'footer.php'; ?>
</body>
</html>
<!--#NoEnv  ; Recommended for performance and compatibility with future AutoHotkey releases.
; #Warn  ; Enable warnings to assist with detecting common errors.
SendMode Input  ; Recommended for new scripts due to its superior speed and reliability.
SetWorkingDir %A_ScriptDir%  ; Ensures a consistent starting directory.-->