<!DOCTYPE html>
<html lang="en">
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Check if user is logged in and UID exists in session
if (isset($_SESSION["user_id"])) {
    header("Location: account.php"); // Redirect to account page if logged in
    exit();
}
// Session variables
$isLoggedIn = $_SESSION["isLogin"] ?? false;
$isAdmin = $_SESSION["isAdmin"] ?? false;
$_login_username = $_SESSION["login_username"] ?? "";
$_login_message = $_SESSION["login_message"] ?? "";
$_SESSION["login_message"] = ""; // Clear message after displaying
$isVerified = ( isset($_GET['recaptchaFlag'], $_GET['checked']) && $_GET['recaptchaFlag'] === '0' &&  $_GET['checked'] === '1'); // If the IP check was done and returned not suspicious, auto-verify
if ($isVerified) {
    $_SESSION['recaptcha_verified'] = true;
} elseif (!isset($_SESSION['recaptcha_verified']) || !$_SESSION['recaptcha_verified']) {
    $_SESSION['recaptcha_verified'] = false;
}
$site_key = '6Left_4qAAAAAGSyUGZfW4CPtYlVch3kqI5NWR6X';
// Normal page
$pageTitle = 'Login';
$pageCSS = 'login.css';
require_once 'head.php';
?>
<body>
    <main class="login_main">
        <!-- Original Login Form -->
        <form action="Class_account.php" method="post" id="login-form" class="form_login" onsubmit="return validateLoginForm();">
            <div class="imgcontainer">
                <img src="Multimedia/login.png" alt="Login" class="login">
                <h3>Register</h3>
            </div>
            <div class="container">
                <!-- For Firebase email sign-in, treat this as the email field -->
                <!--<input type="text" class="form-control" name="username" id="username" autocomplete="username" placeholder="" required>
                    <label for="username"><b>Enter Username</b></label>-->
                <div class="form-floating mt-1">
                    <input type="email" class="form-control" name="email" id="createEmail" autocomplete="username" placeholder="" required>
                    <label for="createEmail"><b>Enter Email</b></label>
                </div>

                <div class="form-floating my-2">
                    <input type="password" class="form-control" name="password" id="createPassword" autocomplete="current-password" placeholder="" required>
                    <label for="createPassword"><b>Enter Password</b></label>
                </div>

                <!-- Login triggers reCAPTCHA and submits form only after success -->
                <button type="submit" class="btn btn-primary mt-3" onclick="handleEmailRegistration()">Login</button>

                <!-- Page controls -->
                <!--<button type="button" class="btn btn-info mt-2" onclick="window.location.href='registration.php';">Register</button>-->
                <button type="button" class="cancelbtn btn btn-danger mt-2" onclick="history.back()">Cancel</button>
            </div>

            <div class="container">
                <!-- reCAPTCHA response token will be stored in this hidden field -->
                <input type="hidden" id="recaptchaToken" name="g-recaptcha-response">
                <!-- reCAPTCHA UI (Can be blinded it to each button with class="g-recaptcha", can add data-size="invisible"/data-action="google_signin")-->
                <?php if (!$_SESSION['recaptcha_verified']): ?>
                    <div style="display: flex; justify-content: center; align-items: center; margin-top: 1px;">
                        <div class="g-recaptcha"
                            data-sitekey="<?php echo $site_key ?>"
                            data-callback="onRecaptchaSuccess"
                            data-error-callback="onRecaptchaError"
                            id="recaptcha-container">
                        </div>
                    </div>
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
                <?php endif; ?>

                <!-- Divider -->
                <hr class="my-3" style="margin: 30px 3;">

                <!-- Alternative login options with Firebase -->
                <div class="social-login text-center">
                    <h3>Or sign in with:</h3>
                    <button id="email-signin-btn" class="btn btn-warning"><i class="bi bi-mailbox2-flag"></i> Email</button>
                    <button id="google-signin-btn" class="btn btn-danger"><i class="bi bi-google"></i> Google</button>
                    <button id="github-signin-btn" class="btn btn-dark"><i class="bi bi-github"></i> GitHub</button>
                    <button id="anonymous-signin-btn" class="btn btn-secondary"><i class="bi bi-person-walking"></i> Anonymous</button>
                </div>

                <!-- Modal for email login -->
                <div id="email-login-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <span class="close" onclick="closeEmailModal()">&times;</span>
                        <h2>Email Sign-In</h2>
                        <div class="form-floating mb-3 position-relative">
                            <input type="email" id="loginEmail" class="form-control" placeholder="Enter email" autocomplete="email">
                            <label for="loginEmail">Email Address</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="password" id="loginPassword" class="form-control" placeholder="Enter password" autocomplete="current-password">
                            <label for="loginPassword">Password</label>
                        </div>
                        <button onclick="handleEmailSignIn()" class="btn btn-primary">Sign In</button>
                    </div>
                </div>

                <!-- FirebaseUI container for authentication widget -->
                <div style="text-align:center;">Firebase UI</div>
                <div id="firebaseui-auth-container"></div>
                <div id="loader"> Loading.... </div>
            </div>
        </form>

        <?php // Display login message if available. 
            if ($_login_message): ?>
            <div class='m-3 text-center'>
                <div class='display-5 text-danger'>
                    <?= $_login_message ?><br>
                </div>
                Please disregard the message above if you are using a social media login.
            </div>
        <?php endif; ?>
    </main>
    <!-- Attaches event listeners to the buttons -->
    <script src="src/js/firebaseUI.js"></script>
    <script src="src/js/IPcheck.js" defer></script>
    <script src="src/js/reCAPTCHA.js" defer></script>
    <script src="src/js/login.js"></script>
    <!-- reCAPTCHAv3 + &hl=zh_tw + ?render=explicit (Not used)-->
    <script src="https://www.google.com/recaptcha/api.js?render=6Lfniv8qAAAAAFd_IKlfvcKGTrKkjda5y2Rat40Z&hl=zh_tw" async defer></script>
    <?php require_once 'footer.php'; ?>
</body>
</html>
<!--#NoEnv  ; Recommended for performance and compatibility with future AutoHotkey releases.
; #Warn  ; Enable warnings to assist with detecting common errors.
SendMode Input  ; Recommended for new scripts due to its superior speed and reliability.
SetWorkingDir %A_ScriptDir%  ; Ensures a consistent starting directory.-->