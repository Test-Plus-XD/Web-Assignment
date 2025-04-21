<?php
ob_start(); // Start output buffering

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the Database class which provides a PDO connection
require_once "Class_db_connect.php";

// Instantiate the Database class and obtain a PDO connection
$DB = new Database();
$conn = $DB->getConnection();

// Determine the request type: if the POST body contains JSON with an "action" field,
// Assume it's an AJAX account action; otherwise, it is a form submission.
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);
$isAJAX = is_array($inputData) && isset($inputData['action']);

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
}

class Account {
    private $conn;                                     // PDO connection object
    private $tbname = "tb_accounts";     // Table name for accounts

    // Constructor: initializes the class with a PDO connection and optional table name.
    // Default table name is "tb_accounts".
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Handles AJAX account actions (logout/delete and Firebase Auth) via JSON input.
    public function handleAccountAction($input) {
        $action = $input['action'] ?? null;
        if (!$action) {
            echo json_encode(["success" => false, "message" => "No action parameter provided"]);
            exit;
        }
        if ($action === "logout") {
            $this->logout();
        } elseif ($action === "delete") {
            $this->delete();
        } elseif ($action === "firebase_register") {
            $this->firebaseRegister($input);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
        exit;
    }

    // Logout Account:
    // Destroys the session, sets isLogin to false, and returns a JSON response.
    public function logout() {
        session_destroy();
        $_SESSION["isLogin"] = false;
        echo json_encode(["success" => true, "message" => "Logout successful"]);
    }

    // Delete Account:
    // Deletes the account from the database based on the logged-in user_id.
    public function delete() {
        $userId = $_SESSION['user_id'] ?? null;
        if (isset($userId)) {
            $sql = "DELETE FROM {$this->tbname} WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(1, $this->user_id);
            if ($stmt->execute([$userId])) {
                echo json_encode(["success" => true, "message" => "Account deleted"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to delete account"]);
            }
            session_destroy();
            $_SESSION["isLogin"] = false;
        } else {
            echo json_encode(["success" => false, "message" => "No user logged in"]);
        }
        exit;
    }

    // Change Password Process:
    // Expects POST form data: password_check, password1, and password2.
    // Validates input, verifies the current password, updates the password, and then redirects.
    public function changePassword() {
        $userId = $_SESSION["user_id"] ?? null;
        $password_check = $_POST["password_check"] ?? "";
        $password1 = $_POST["password1"] ?? "";
        $password2 = $_POST["password2"] ?? "";
        // Create MD5 hash for the new password (Might consider using password_hash() for better security)
        $password_md5 = md5($password1);
        $IsValidated = true;
        // Validation: Check for empty fields
        if (empty($password_check) || empty($password1) || empty($password2)) {    
            $_SESSION["login_message"] = "Fields cannot be empty!";
            $IsValidated = false;
        }
        // Validation: Check if new passwords match
        if ($password1 !== $password2) {
            $_SESSION["login_message"] = "Passwords do not match!";
            $IsValidated = false;
        }
        // Verify current password if validation passed
        if ($IsValidated) {
            $sql = "SELECT password FROM {$this->tbname} WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || md5($password_check) !== $row['password']) {
                $_SESSION["login_message"] = "Current password is incorrect";
                $IsValidated = false;
            }
        }
        // Update password if all validations pass
        if ($IsValidated) {
            $sql = "UPDATE {$this->tbname} SET password = ? WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt->execute([$password_md5, $userId])) {
                $_SESSION["login_message"] = "Password updated successfully";
            } else {
                $_SESSION["login_message"] = "Failed to update password";
            }
        }
        header("Location: change_password.php");
        exit;
    }

    // Login Process:
    // Expects POST form data: username and password.
    // Validates input, checks credentials, sets session variables, retrieves owned product count, and outputs JavaScript to store the count and redirect.
    public function login() {
        $username = $_POST['username'] ?? "";
        $password = $_POST['password'] ?? "";
        $_SESSION["login_message"] = "";
        if (empty($username) || empty($password)) {
            $_SESSION["login_message"] = "Fields cannot be empty!";
            header("Location: login.php");
            exit;
        }
        $password_md5 = md5($password);
        $sql = "SELECT user_id, username, password, isAdmin FROM {$this->tbname} WHERE username = ? AND password = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username, $password_md5]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            // Store login session data
            $_SESSION["isLogin"] = true;
            $_SESSION["username"] = $row["username"];
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["isAdmin"] = $row["isAdmin"];
            if ($_SESSION["isAdmin"] == 1){
                $_SESSION["isAdmin"] = true;
                $_SESSION["login_message"] = "Administrator Login successful!";
            } else {
                $_SESSION["isAdmin"] = false;
                $_SESSION["login_message"] = "Login successful!";
            }
            // Fetch owned product count from tb_owned_products table
            $sql = "SELECT COUNT(*) AS product_count FROM tb_owned_products WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$row["user_id"]]);
            $owned_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $library_count = $owned_data["product_count"] ?? 0;
            // Output JavaScript to pass library count to localStorage and redirect to login.php
            echo "<script>
                    localStorage.setItem('libraryCount', " . json_encode($library_count) . ");
                    console.log('Library count updated to:', " . json_encode($library_count) . ");
                    window.location.href = 'login.php';
                  </script>";
            $_SESSION['recaptcha_verified'] = false;
            exit;
        } else {
            $_SESSION["login_message"] = "Username or password is incorrect!";
            header("Location: login.php");
            exit;
        }
    }

    // Register Account Process:
    // Expects POST form data: fullname, username, password1, and password2.
    // Validates input, checks for existing username, inserts a new account if valid, sets a session message, and then redirects to registration.php.
    public function register() {
        // Get variables from POST
        // Prevent SQL/XSS injection
        $fullname = htmlspecialchars(strip_tags($_POST["fullname"]));
        $username = htmlspecialchars(strip_tags($_POST["username"]));
        $password1 = $_POST["password1"] ?? "";
        $password2 = $_POST["password2"] ?? "";
        // Create MD5 hash for the password (consider using password_hash() in production)
        $password_md5 = md5($password1);
        $IsValidated = true;
        // Validation: Check for empty fields
        if (empty($fullname) || empty($username) || empty($password1) || empty($password2)) {
            $_SESSION["login_message"] = "Fields cannot be empty!";
            $IsValidated = false;
        }
        // Validation: Check if new passwords match
        if ($password1 !== $password2) {
            $_SESSION["login_message"] = "Passwords do not match!";
            $IsValidated = false;
        }
        // Check if username already exists
        $sql = "SELECT * FROM {$this->tbname} WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $_SESSION["login_message"] = "Username already exists!";
            $IsValidated = false;
        }
        // Proceed with insertion if validation passes
        if ($IsValidated) {
            $sql = "INSERT INTO {$this->tbname} (fullname, username, password) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            if ($stmt->execute([$fullname, $username, $password_md5])) {
                $last_id = $this->conn->lastInsertId();
                $_SESSION["login_message"] = "User account ($username) created! Your ID is: ($last_id)";
            } else {
                $_SESSION["login_message"] = "User account creation failed!";
            }
        }
        // Redirect back to registration.php regardless of success or failure
        header("Location: registration.php");
        exit;
    }

    // Handle Firebase user registration (via AJAX)
    public function firebaseRegister($input) {
        $fullname = htmlspecialchars(strip_tags($input["displayName"] ?? ""));
        $email = $input["email"] ?? "";
        $username = explode("@", $email)[0];
        $uid = $input["uid"] ?? null;

        if (empty($fullname) || empty($username) || empty($uid)) {
            echo json_encode(["success" => false, "message" => "Missing Firebase user data"]);
            return;
        }

        // Check if username already exists
        $check = $this->conn->prepare("SELECT user_id FROM {$this->tbname} WHERE username = ?");
        $check->execute([$username]);
        if ($check->rowCount() > 0) {
            $user = $check->fetch(PDO::FETCH_ASSOC);
            $_SESSION["isLogin"] = true;
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["isAdmin"] = false;
            echo json_encode(["success" => true, "message" => "Firebase login session restored"]);
            return;
        }

        // Insert new Firebase user with NULL password
        $sql = "INSERT INTO {$this->tbname} (fullname, username, password) VALUES (?, ?, NULL)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute([$fullname, $username])) {
            $userId = $this->conn->lastInsertId();
            $_SESSION["isLogin"] = true;
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = $userId;
            $_SESSION["isAdmin"] = false;
            echo json_encode(["success" => true, "message" => "Firebase user registered", "user_id" => $userId]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to register Firebase user"]);
        }
    }
}
// Dispatching requests based on method and input.
// Instantiate the Account class
$accountManager = new Account($conn);

// If the request method is POST, determine which process to run.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If there is JSON input with an "action" field, assume an AJAX account action.
    if ($isAJAX) {
        //$accountManager->handleAccountAction();
        $accountManager->handleAccountAction($inputData);
        exit;
    }
    // Otherwise, check for PHP form submissions.
    elseif (isset($_POST['password_check'], $_POST['password1'], $_POST['password2'])) {
        // Update Password process
        $accountManager->changePassword();
        exit;
    }
    elseif (isset($_POST['username'], $_POST['password']) && !isset($_POST['fullname'])) {
        // Login process
        $accountManager->login();
        exit;
    }
    elseif (isset($_POST['fullname'], $_POST['username'], $_POST['password1'], $_POST['password2'])) {
        // Registration process
        $accountManager->register();
        exit;
    }
}
ob_end_flush();
?>