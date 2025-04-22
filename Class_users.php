<?php
// Include Firestore configuration, constants, and API trait
require_once 'firestore.php';

class Users {
    // Import Firestore REST helpers and parsers from the trait
    use FirestoreApiTrait;

    // The Firestore collection where user accounts live
    private const COLLECTION_NAME = 'tb_accounts';

    // Tell the trait which collection to operate on
    protected function getCollectionName(): string {
        return self::COLLECTION_NAME;
    }

    // List all user documents
    public function listUsers(): array {
        // GET /tb_accounts
        // Example: curl -X GET http://your-domain.com/Class_users.php/all
        $response = $this->_makeRequest('', 'GET');
        $users = [];
        if (isset($response['documents'])) {
            foreach ($response['documents'] as $document) {
                $users[] = $this->_parseFirestoreDocument($document);
            }
        }
        return $users;
    }

    // Fetch a single user by their Firebase UID
    public function getUserByUid(string $uid): ?array {
        $url = FIRESTORE_API_BASE_URL . ':runQuery?key=' . $this->apiKey;
        $ch = curl_init($url);

        $queryData = [
            'structuredQuery' => [
                'from' => [['collectionId' => self::COLLECTION_NAME]],
                'where' => [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'uid'],
                        'op' => 'EQUAL',
                        'value' => ['stringValue' => $uid],
                    ],
                ],
                'limit' => 1,
            ],
        ];
        $jsonData = json_encode($queryData);
        // Apply common Firestore cURL options for POST (runQuery uses POST).
        setFirestoreCurlOptions($ch, 'POST');
        // Attach the JSON-encoded query payload to the request body.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        // Execute the cURL request.
        $response  = curl_exec($ch);
        // Get the HTTP status code.
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Check for cURL errors.
        $curlError = curl_error($ch);
        // Close the cURL session.
        curl_close($ch);

        if ($curlError) {
            error_log("Firestore API cURL Error for getUserByUid: " . $curlError);
            return ['error' => 'cURL Error', 'details' => $curlError];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Firestore API HTTP Error {$httpCode} for getUserByUid: " . $response);
            $decoded = json_decode($response, true);
            return $decoded ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response];
        }

        $decodedResponse = json_decode($response, true);
        if (isset($decodedResponse[0]['document'])) {
            return $this->_parseFirestoreDocument($decodedResponse[0]['document']);
        }
        return null;
    }

    public function getUser(string $id): array {
        $response = $this->_makeRequest($id, 'GET');
        if (isset($response['fields'])) {
            return $this->_parseFirestoreDocument($response);
        }
        return $response;
    }

    public function handleCreateUser(array $data): void {
        if (isset($data['uid']) && $this->checkUserExistsByUid($data['uid'])) {
            echo json_encode(['success' => true, 'message' => 'User already exists']);
        } else {
            $result = $this->insertUser($data);
            echo json_encode($result);
        }
    }

    public function insertUser(array $data): array {
        return $this->_makeRequest('', 'POST', $data);
    }

    public function updateUser(string $id, array $data): array {
        return $this->_makeRequest($id, 'PATCH', $data);
    }

    public function deleteUser(string $id): array {
        return $this->_makeRequest($id, 'DELETE');
    }

    private function deleteUserByUid(string $uid): array {
        $user = $this->getUserByUid($uid);
        if ($user && isset($user['id'])) { // Use 'id' as it's the document ID now
            return $this->deleteUser($user['id']);
        } else {
            return ['error' => 'User not found with UID: ' . $uid];
        }
    }

    private function checkUserExistsByUid(string $uid): bool {
        $user = $this->getUserByUid($uid);
        return $user !== null;
    }
    
    // Kreait functions for deleting a OAuth user account
    public function deleteFirebaseAuthAccount($uid) {
        require_once 'firebase_admin.php'; // Use shared Firebase Auth instance
        global $auth;
        try {
            $auth->deleteUser($uid); // Delete user via Admin SDK
            return ['status' => 'success', 'message' => "User with UID $uid deleted from Firebase Auth."];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Failed to delete Firebase Auth user: ' . $e->getMessage()];
        }
    }
}

// Endpoint handler block for Users, routes requests based on PATH_INFO and HTTP method to output JSON
if (isset($_SERVER['PATH_INFO'])) {
    $pathInfo = trim($_SERVER['PATH_INFO'], '/'); // Get the path information from the URL and trim leading/trailing slashes
    $segments = explode('/', $pathInfo); // Split the path information into an array of segments
    $method = $_SERVER['REQUEST_METHOD']; // Get the HTTP request method (GET, POST, PUT, DELETE, etc.)
    $users = new Users(); // Create an instance of the Users class
    header('Content-Type: application/json'); // Set the Content-Type header to application/json for API responses

    if ($method === 'GET') {
        if (isset($segments[0]) && $segments[0] === 'user' && isset($segments[1])) {
            // Handle GET request for /user/{id} to retrieve a user by their document ID
            // Example cURL command: curl -X GET http://your-domain.com/Class_users.php/user/some_document_id
            echo json_encode($users->getUser($segments[1]));
        } elseif (isset($segments[0]) && $segments[0] === 'uid' && isset($segments[1])) {
            // Handle GET request for /uid/{id} to retrieve a user by their Firebase UID
            // Example cURL command: curl -X GET http://your-domain.com/Class_users.php/uid/some_firebase_uid
            echo json_encode($users->getUserByUid($segments[1]));
        } elseif (isset($segments[0]) && $segments[0] === 'all') {
            // Handle GET request for /all to retrieve all user records
            // Example cURL command: curl -X GET http://your-domain.com/Class_users.php/all
            echo json_encode($users->listUsers());
        } else {
            http_response_code(400); // Set the HTTP response code to 400 (Bad Request)
            echo json_encode(['error' => 'Invalid GET request']); // Return a JSON error message for invalid GET requests
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true); // Decode the JSON data sent in the request body
        if (isset($segments[0]) && $segments[0] === 'uid') {
            // Handle POST request to /uid to insert a new user record
            // Example cURL command: curl -X POST -H "Content-Type: application/json" -d '{"uid": "new_uid", "email": "new@example.com"}' http://your-domain.com/Class_users.php/uid
            echo json_encode($users->insertUser($data));
        }
        // Handle POST request to /create for user registration (checks for existing user)
        elseif (isset($segments[0]) && $segments[0] === 'create') {
            $data = json_decode(file_get_contents('php://input'), true); // Decode the JSON data sent in the request body
            // Example cURL command: curl -X POST -H "Content-Type: application/json" -d '{"uid": "existing_or_new_uid", "email": "user@example.com"}' http://your-domain.com/Class_users.php/create
            $users->handleCreateUser($data); // Call the public method
        }
        else {
            http_response_code(400); // Set the HTTP response code to 400 (Bad Request)
            echo json_encode(['error' => 'Invalid POST request']); // Return a JSON error message for invalid POST requests
        }
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        // Handle PUT or PATCH request for /uid/{id} to update an existing user record
        if (isset($segments[0], $segments[1]) && $segments[0] === 'uid') {
            $data = json_decode(file_get_contents('php://input'), true); // Decode the JSON data from the request body
            // Example cURL command (PUT): curl -X PUT -H "Content-Type: application/json" -d '{"email": "updated@example.com"}' http://your-domain.com/Class_users.php/uid/some_firebase_uid
            // Example cURL command (PATCH): curl -X PATCH -H "Content-Type: application/json" -d '{"email": "updated@example.com"}' http://your-domain.com/Class_users.php/uid/some_firebase_uid
            echo json_encode($users->updateUser($segments[1], $data));
        } else {
            http_response_code(400); // Set the HTTP response code to 400 (Bad Request)
            echo json_encode(['error' => 'Invalid PUT/PATCH request']); // Return a JSON error message for invalid PUT or PATCH requests
        }
    } elseif ($method === 'DELETE') {
        if (isset($segments[0]) && $segments[0] === 'auth' && isset($segments[1])) {
             // Handle DELETE request for /auth/{uid} to delete a user OAuth account by Firebase UID
            // Example cURL command: curl -X DELETE http://your-domain.com/Class_users.php/auth/some_firebase_uid
            echo json_encode($users->deleteFirebaseAuthAccount($segments[1]));
        } elseif (isset($segments[0]) && $segments[0] === 'user' && isset($segments[1])) {
            // Handle DELETE request for /user/{id} to delete a user record by document ID
            // Example cURL command: curl -X DELETE http://your-domain.com/Class_users.php/user/some_document_id
            echo json_encode($users->deleteUser($segments[1]));
        } elseif (isset($segments[0]) && $segments[0] === 'uid' && isset($segments[1])) {
            // Handle DELETE request for /uid/{id} to delete a user record and OAuth account by Firebase UID
            // Example cURL command: curl -X DELETE http://your-domain.com/Class_users.php/uid/some_firebase_uid
            echo json_encode($users->deleteFirebaseAuthAccount($segments[1]));
            echo json_encode($users->deleteUserByUid($segments[1]));
        } else {
            http_response_code(400); // Set the HTTP response code to 400 (Bad Request)
            echo json_encode(['error' => 'Invalid DELETE request']); // Return a JSON error message for invalid DELETE requests
        }
    } else {
        http_response_code(405); // Set the HTTP response code to 405 (Method Not Allowed)
        echo json_encode(['error' => 'Method not allowed']); // Return a JSON error message for unsupported HTTP methods
    }
}
?>