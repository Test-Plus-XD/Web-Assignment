<!DOCTYPE html>
<html lang="en">
<?php
$pageTitle = 'Account';
$pageCSS = 'account.css';
include 'head.php';
// Check if user is logged in and UID exists in session
if (!isset($_SESSION["user_id"])) {
    // Redirect to login page if not logged in
    echo <<<HTML
    <script>
        function handleNonAjax() {window.location = 'login.php';}
        handleNonAjax();
    </script>
HTML;
    exit();
}
$userId = $_SESSION["user_id"];
$apiEndpoint = 'http://localhost/Web%20Assignment/Class_users.php/uid/' . $userId;
// Fetch user data from Class_users.php
$response = file_get_contents($apiEndpoint);

if ($response === false) {
    // Handle error if API request fails
    $userData = null;
    $fetchError = "Failed to fetch account data.";
} else {
    $decodedResponse = json_decode($response, true);
    if (is_array($decodedResponse)) {
        $userData = $decodedResponse;
    } else {
        $userData = null;
        $fetchError = json_last_error_msg() ?: "Failed to decode account data or invalid response format.";
    }
}
?>
<script src="src/js/account_management.js" defer></script>
<body style="font-family:Zen Maru Gothic">
    <main class="account_main">
        <div class="container px-4" id="account_container">
            <div class="row d-flex justify-content-evenly gx-4">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12 text-center">
                    <?php if ($userData && isset($userData['photoURL'])): ?>
                        <img src="<?php echo htmlspecialchars($userData['photoURL']); ?>" alt="User Photo" style="width: 200px; height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <img src="Multimedia/login.png" alt="Default Login Photo" class="login">
                    <?php endif; ?>
                </div>

                <?php if ($userData): ?>
                    <?php foreach ($userData as $key => $value): ?>
                        <?php if ($key === 'ID') continue; // Skip displaying document ID in the grid ?>
                        <?php if ($key === 'photoURL') continue; // Skip displaying PhotoURL in the grid ?>
                        <?php if ($key === 'isAdmin' && $value === false) continue; // Hide isAdmin if false ?>
                        <?php if ($value !== null && (!is_array($value) || !empty($value) || $value == "")): ?>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6 p-2 mh-3 account-info-item">
                                <b><?php echo htmlspecialchars(ucfirst($key)); ?> :</b>
                                <span>
                                    <?php
                                    if (is_array($value)) {
                                        echo htmlspecialchars(implode(', ', $value)); // Join array elements with a comma
                                    } else {
                                        echo htmlspecialchars(is_bool($value) ? ($value ? 'True' : 'False') : (string)$value);
                                    }
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php elseif (isset($fetchError)): ?>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12 p-2 text-danger">
                        <b>Error:</b> <?php echo htmlspecialchars($fetchError); ?>
                    </div>
                <?php else: ?>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12 p-2">
                        <b>Error:</b> Could not retrieve account information.
                    </div>
                <?php endif; ?>

                <div class="col-lg-12 col-md-12 col-sm-12 col-12 mt-3 d-flex justify-content-evenly">
                    <div class="p-2">
                        <button id="logoutButton" class="btn btn-warning">Logout</button>
                    </div>
                    <div class="p-2">
                        <button id="changeButton" class="btn btn-warning" onclick="window.location.href='change_password.php';">Change Password</button>
                    </div>
                    <div class="p-2">
                        <button id="deleteButton" class="btn btn-danger">Delete Account</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="src/js/firebase_cdn.js"></script>
    <?php include 'footer.php'; ?>
</body>
</html>