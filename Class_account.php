<?php
// Start output buffering to prevent premature output
ob_start();
// Start session or resume existing one
session_start();

// Ensure this endpoint is only called via AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
if (!$isAjax) {
    // If it's not an AJAX request, redirect to the login page
    //header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <script>
        function handleNonAjax() {window.location = 'login.php';}
        handleNonAjax();
    </script>
HTML;
    echo json_encode(['success' => false, 'message' => 'This endpoint only accepts AJAX requests.']); // This might not even be sent before the redirect
    http_response_code(400); // While redirecting, setting a 400 might not be necessary
    exit; // Make sure to exit after sending the header
}

// Send all responses as JSON
header('Content-Type: application/json');

// Include Firestore configuration (defines FIREBASE_PROJECT_ID, FIRESTORE_API_BASE_URL, etc.)
require_once 'firestore.php';

// Import Firebase and Firestore classes (assuming you are using these elsewhere in the class)
use Firebase\Auth\FirebaseAuth;
use Google\Cloud\Firestore\FirestoreClient;

// Define Account class to handle logout, delete, and update session actions
class Account {
    // Assuming FirebaseAuth and FirestoreClient are only needed for delete(),
    // keeping them nullable properties and initializing them only when needed.
    private ?FirebaseAuth $auth = null;
    private ?FirestoreClient $firestore = null;

    // Constructor
    public function __construct() {
        // Initialization logic if needed
    }

    // Route based on action parameter: 'logout', 'delete', or 'update_session'
    public function handleAction(string $action): void {
        if ($action === 'logout') {
            $this->logout();
        } elseif ($action === 'delete') {
            $this->delete();
        } elseif ($action === 'update_session') {
            // Assuming data is sent via POST for update_session action
            $this->updateFirebaseSession($_POST);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            exit;
        }
    }

    // Destroy the user session and respond with success JSON
    private function logout(): void {
        error_log("Logout function called");
        // headers are already set at the top, but setting Content-Type here ensures it for this specific response
        header('Content-Type: application/json');
        $_SESSION = [];       // Clear all session variables
        session_destroy();      // Destroy the session data on the server
        $response = ["success" => true, "message" => "Logout successful"];
        echo json_encode($response);
        error_log("Logout response echoed");
        exit();
    }

    // Delete the user from Firebase Auth and Firestore, then clear session
    private function delete(): void {
        $uid = $_SESSION['user_id'] ?? null; // Get Firebase UID from session
        if (!$uid) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No user logged in'
            ]);
            exit;
        }
        /* Moved to Class_users.php
        // Initialize Firebase Admin SDK here, only when needed for delete
        try {
            $serviceAccount = require __DIR__ . '/path/to/serviceAccountKey.json';
            $this->auth     = FirebaseAuth::withServiceAccount($serviceAccount); // Init Firebase Auth
             // Assuming FIREBASE_PROJECT_ID is defined in firestore.php
            $this->firestore = new FirestoreClient(['projectId' => FIREBASE_PROJECT_ID]); // Init Firestore
        } catch (Exception $e) {
            error_log("Firebase Admin SDK initialization error in delete(): " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server configuration error during account deletion.'
            ]);
            exit;
        }
        */
        try {
            /* Attempt to delete from Firebase Auth
            $this->auth->deleteUser($uid);
            error_log("User deleted from Firebase Auth: " . $uid);

            // Attempt to delete from Firestore via API
            $result = $this->callUsersDeleteEndpoint($uid);
            error_log("Firestore deletion API call result for UID " . $uid . ": " . print_r($result, true));
            */
            // Clear session regardless of deletion success in external services
            $_SESSION = [];       // Clear session variables
            session_destroy();      // Destroy session
            error_log("Session cleared for UID: " . $uid);

            // Prepare final response based on Firestore deletion result
            if (isset($result['error'])) {
                // Partial success: deleted from Auth but Firestore deletion failed
                echo json_encode([
                    'success' => true, // Success from the perspective of the primary action (logging out and attempting deletion)
                    'message' => 'Account deleted from Firebase, but failed to delete from Firestore.'
                ]);
            } else {
                // Full success
                echo json_encode([
                    'success' => true,
                    'message' => 'Account deleted successfully'
                ]);
            }
        } catch (\Firebase\Auth\UserNotFoundException $e) {
            error_log("User not found in Firebase Auth during deletion: " . $uid);
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found in Firebase'
            ]);
        } catch (\Exception $e) {
            error_log("General error deleting account for UID " . $uid . ": " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete account'
            ]);
        }
        exit;
    }

    // Call Class_users.php DELETE endpoint to remove Firestore record
    private function callUsersDeleteEndpoint(string $uid): ?array {
        // Assuming Class_users.php is accessible via a local HTTP request
        $baseUrl = 'http://localhost/Web%20Assignment/'; // Define your base URL for internal API calls
        $url = $baseUrl . 'Class_users.php/uid/' . $uid;
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); // Use HTTP DELETE
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as string
         // Add headers to ensure it's treated as AJAX if needed by Class_users.php
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);


        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("cURL Error deleting user via Class_users.php: $error");
            return ['error' => 'cURL error', 'details' => $error];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $decodedResponse = json_decode($response, true);
            return $decodedResponse ?: ['success' => true, 'message' => 'User deleted from Firestore via API.']; // Assume success if 2xx but no JSON body
        } else {
             $decodedResponse = json_decode($response, true);
            error_log("HTTP $httpCode error deleting user via Class_users.php: " . ($response ? $response : 'No response body'));
            return ['error' => "HTTP Error", 'details' => $decodedResponse ?: $response]; // Include decoded response or raw if decoding fails
        }
    }


    // Updates the PHP session after a successful Firebase login.
    // Sets session variables and includes key info in the JSON response for client-side use.
    // @param array $data An associative array containing the request data, expected to have 'firebaseLogin' set to true and the Firebase 'uid'.
    public function updateFirebaseSession(array $data): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check for required data in the input
        if (!isset($data['firebaseLogin']) || $data['firebaseLogin'] !== 'true' || !isset($data['uid'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "user_id" => null,
                "session_id" => session_id(),
                "isAdmin" => false,
                "message" => "Invalid request: Missing firebaseLogin or uid."
            ]);
            exit();
        }

        // If input is valid, set core session variables
        $_SESSION['isLogin'] = true;
        $_SESSION["user_id"] = $data['uid']; // Save the Firebase UID
        $_SESSION['login_message'] = "Logged in via Firebase";

        // Initialize the response array with successful core info and defaults
        $apiResponse = [
            "success" => true,
            "user_id" => $_SESSION["user_id"],
            "session_id" => session_id(),
            "isAdmin" => false, // Default isAdmin to false
            "message" => "Session updated successfully, fetching admin status."
        ];

        // Call Class_users.php API to get user data
        $uid = $data['uid'];
        $baseUrl = 'http://localhost/Web%20Assignment/'; // Define base URL
        $usersApiUrl = $baseUrl . 'Class_users.php/uid/' . $uid;
        $ch = curl_init($usersApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
        // Add headers to ensure it's treated as AJAX
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);


        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch); // Capture cURL errors
        error_log("Raw API Response from Class_users.php: " . ($response ? $response : 'No response received')); // Log response or indicate no response
        curl_close($ch);

        //Update apiResponse based on cURL result
        if ($curlError) {
            // Handle cURL errors
            error_log("cURL Error fetching user data from Class_users.php API for UID " . $uid . ": " . $curlError);
            $apiResponse["isAdmin"] = false; // Ensure isAdmin is false on cURL error
            $apiResponse["message"] = "Could not retrieve admin status due to API connection error.";
            // Keep success as true if session update was successful, just admin status failed
        } elseif ($httpCode === 200) {
            // Handle successful HTTP response from Class_users.php
            $userData = json_decode($response, true);
            if ($userData && isset($userData['isAdmin'])) {
                $_SESSION["isAdmin"] = (bool) $userData['isAdmin']; // Set isAdmin status in session
                $apiResponse["isAdmin"] = $_SESSION["isAdmin"]; // Include isAdmin in the response
                $apiResponse["message"] = "Session and admin status updated successfully.";
            } else {
                // Handle case where 200 received but isAdmin is missing or data is unexpected
                $_SESSION["isAdmin"] = false; // Default to false if isAdmin is not found
                $apiResponse["isAdmin"] = false; // Include isAdmin as false in the response
                $apiResponse["message"] = "Admin status not found in API response.";
                error_log("Admin status missing in Class_users.php API response for UID: " . $uid . ". Response: " . ($response ? $response : 'No response body'));
            }
        } elseif ($httpCode === 404) {
            // Handle user not found in Class_users.php
            $_SESSION["isAdmin"] = false; // User not found, default to false
            $apiResponse["isAdmin"] = false; // Include isAdmin as false
            $apiResponse["message"] = "User not found in database, admin status defaulted to false.";
            error_log("User not found in Class_users.php API for UID: " . $uid);
        } else {
            // Handle other HTTP errors from Class_users.php
            $_SESSION["isAdmin"] = false; // Default to false on other errors
             $apiResponse["isAdmin"] = false; // Include isAdmin as false
            $apiResponse["message"] = "Error fetching admin status from API. HTTP Status: " . $httpCode;
            error_log("Error fetching admin status from Class_users.php API for UID " . $uid . ". HTTP Status: " . $httpCode . ", Response: " . ($response ? $response : 'No response body'));
        }
        http_response_code(200); // Set the final HTTP status code for the client response (usually 200 for successful session update)
        header('Content-Type: application/json'); // Ensure the content type is JSON
        echo json_encode($apiResponse); // Output the final JSON response
        exit(); // Exit to stop further script execution
    }
}

// Instantiate Account
$accountManager = new Account();
// Decode the incoming JSON request body (primarily for logout/delete via POST body)
// This block is for actions sent in the JSON body of a POST request.
// The update_firebase_session action is handled separately via $_POST data.
$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;
// Check for a specific action to update the Firebase session via POST (using URL query parameter)
// This block handles the client-side fetch call from login.js
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update_firebase_session') {
    // Pass the entire $_POST array to the updateFirebaseSession method
    $accountManager->updateFirebaseSession($_POST);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && is_array($input) && isset($action)) {
    // Handle other actions from POST body (like 'logout' or 'delete' if they were sent this way)
    // Currently, handleAction() also routes logout/delete. This might be redundant or intended for different call methods.
    // Based on your handleAction, it seems logout and delete were intended to be handled this way.
    $accountManager->handleAction($action);
}
// Note: If a GET request with an 'action' parameter for logout/delete is intended,
// you would need another elseif block here checking $_SERVER['REQUEST_METHOD'] === 'GET'
// and checking $_GET['action']. Be mindful of security implications for GET requests that
// perform state-changing operations like logout or delete.

// Flush output buffer and end
ob_end_flush();
?>