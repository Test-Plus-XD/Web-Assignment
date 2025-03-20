<link rel="stylesheet" href="css/login.css">
<?php
//Session handling
session_start();
$isLoggedIn = isset($_SESSION["isLogin"]) ? $_SESSION["isLogin"] : false; // Check if user is logged in
$_login_username = $_SESSION["login_username"] = "";
$_login_message = $_SESSION["login_message"] = "";
var_dump($_SESSION);

if (isset($_POST["username"])){
    $_login_username = $_POST["username"];
    $_password = $_POST["pw"];

    if (empty($_login_username) || empty($_password)){
        $_SESSION["isLogin"] = false;
        $_SESSION["login_username"] = "";
        $_SESSION["login_message"] = "Username or password cannot be empty.";        
    } else {
        // Check credential
        if ($_login_username == "username" && $_password == "password") {
            $_SESSION["isLogin"] = true;
            $_SESSION["login_username"] = $_login_username;
            //$_SESSION["login_message"] = "Login successful";
            header("Location: index.php");
        } else {
            $_SESSION["isLogin"] = false;
            $_SESSION["login_username"] = "";
            $_SESSION["login_message"] = "Login failure, Invalid username or password";
        }
    }
}
?>

<script src="js/index.js"></script>
<script>
    const isLoggedIn = <?php echo json_encode($_SESSION["isLogin"]); ?>; // Pass PHP session value to JS
    console.log("Is logged in?: ", isLoggedIn); // Debugging purpose
</script>

<?php
// Rediect
echo "<script>window.location.href='login.php';</script>";

// Debug
echo "isLogin = " . $_SESSION["isLogin"] . "<br>";
echo "login_username = " . $_SESSION["login_username"] . "<br>";
echo "login_message = " . $_SESSION["login_message"] . "<br>";
echo "<a href='login.php'>Login</a>";
?>