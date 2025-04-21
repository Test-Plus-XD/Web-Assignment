<?php
require_once 'firestore.php'; // Include the Firestore configuration file
class Users {
    private const COLLECTION_NAME = 'tb_accounts'; // Define a constant for the Firestore collection name ('tb_accounts')
    private string $baseUrl; // Declare a private property to store the base URL for Firestore API calls
    private string $apiKey;      // Declare a private property to store the Firebase API key

    public function __construct() {
        $this->baseUrl = FIRESTORE_API_BASE_URL . '/' . self::COLLECTION_NAME; // Construct the base URL by combining the Firestore base URL and the collection name
        $this->apiKey = FIREBASE_API_KEY; // Assign the Firebase API key from the configuration to the $apiKey property
    }

    private function _makeRequest(string $endpoint = '', string $method = 'GET', ?array $data = null): ?array {
        // Private method to handle making HTTP requests to the Firestore API
        $url = $this->baseUrl . '/' . $endpoint . '?key=' . $this->apiKey; // Construct the full API URL by appending the endpoint and API key
        $ch = curl_init($url); // Initialize a cURL session

        setFirestoreCurlOptions($ch, $method); // Apply common cURL options based on the HTTP method (defined in firestore.php)

        if ($data) {
            // Check if there is data to send in the request
            $jsonData = json_encode($data); // Encode the PHP array data into a JSON string
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Set the POST fields of the cURL request to the JSON data
            error_log("Firestore API Payload: " . $jsonData); // Log the JSON payload for debugging purposes
        }

        $response = curl_exec($ch); // Execute the cURL session and store the response
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get the HTTP status code of the response
        $curlError = curl_error($ch); // Get any error information from the last cURL operation
        curl_close($ch); // Close the cURL session

        if ($curlError) {
            // Check if there was a cURL error
            error_log("Firestore API cURL Error for Users: " . $curlError); // Log the cURL error
            return ['error' => 'cURL Error', 'details' => $curlError]; // Return an array indicating a cURL error with details
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            // Check if the HTTP status code indicates an error (not in the 2xx range)
            error_log("Firestore API HTTP Error {$httpCode} for Users: " . $response); // Log the HTTP error with the status code and response
            $decoded = json_decode($response, true); // Decode the JSON response into a PHP array
            return $decoded ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response]; // Return the decoded response or a generic HTTP error array with details
        }
        return json_decode($response, true); // If the request was successful, decode the JSON response and return it as a PHP array
    }

    public function displayAll(): array {
        // Public method to retrieve and display all user records from Firestore
        $response = $this->_makeRequest('', 'GET'); // Make a GET request to the base URL to retrieve all documents in the collection
        $users = []; // Initialize an empty array to store user data
        if (isset($response['documents'])) {
            // Check if the response contains a 'documents' key
            foreach ($response['documents'] as $document) {
                // Iterate through each document in the response
                $users[] = $this->_parseFirestoreDocument($document); // Parse each Firestore document into a simpler format and add it to the $users array
            }
        }
        return $users; // Return the array of simplified user records
    }

    public function getUserByUid(string $uid): ?array {
        // Public method to retrieve a user record from Firestore based on their UID
        $url = FIRESTORE_API_BASE_URL . ':runQuery?key=' . $this->apiKey; // Construct the URL for running a query in Firestore
        $ch = curl_init($url); // Initialize a cURL session

        $queryData = [
            'structuredQuery' => [
                'from' => [['collectionId' => self::COLLECTION_NAME]], // Specify the collection to query from
                'where' => [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'uid'], // Specify the field to filter on (uid)
                        'op' => 'EQUAL', // Specify the comparison operator (equal to)
                        'value' => ['stringValue' => $uid], // Specify the value to compare against (the provided $uid)
                    ],
                ],
                'limit' => 1, // Limit the query to return only one document
            ],
        ];

        $jsonData = json_encode($queryData); // Encode the query data into a JSON string
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // Set the HTTP request method to POST for running a query
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Set the POST fields to the JSON query data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // Set the Content-Type header to application/json

        $response = curl_exec($ch); // Execute the cURL session and store the response
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get the HTTP status code
        $curlError = curl_error($ch); // Get any cURL errors
        curl_close($ch); // Close the cURL session

        if ($curlError) {
            // Check for cURL errors
            error_log("Firestore API cURL Error for getUserByUid: " . $curlError); // Log the error
            return ['error' => 'cURL Error', 'details' => $curlError]; // Return an error array
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            // Check for HTTP errors
            error_log("Firestore API HTTP Error {$httpCode} for getUserByUid: " . $response); // Log the error
            $decoded = json_decode($response, true); // Decode the response
            return $decoded ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response]; // Return the decoded response or an error array
        }

        $decodedResponse = json_decode($response, true); // Decode the successful response
        if (isset($decodedResponse[0]['document'])) {
            // Check if a document was found in the query results
            return $this->_parseFirestoreDocumentSingle($decodedResponse[0]['document']); // Parse the found document and return it
        }
        return null; // Return null if no user was found with the given UID
    }

    public function getUser(string $id): array {
        // Public method to retrieve a single user record from Firestore by its document ID
        $response = $this->_makeRequest($id, 'GET'); // Make a GET request to retrieve the document with the specified ID
        if (isset($response['fields'])) {
            // Check if the response contains a 'fields' key (indicating a successful retrieval)
            return $this->_parseFirestoreDocumentSingle($response); // Parse the Firestore document and return it
        }
        return $response; // Return the raw response (likely an error) if 'fields' is missing
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
        // Public method to insert a new user record into Firestore
        return $this->_makeRequest('', 'POST', ['fields' => $this->_prepareFields($data)]); // Make a POST request to the collection base URL with the prepared fields as data
    }

    public function updateUser(string $id, array $data): array {
        // Public method to update an existing user record in Firestore by its document ID
        return $this->_makeRequest($id, 'PATCH', ['fields' => $this->_prepareFields($data)]); // Make a PATCH request to the document URL with the prepared fields as data
    }

    public function deleteUser(string $id): array {
        // Public method to delete a user record from Firestore by its document ID
        return $this->_makeRequest($id, 'DELETE'); // Make a DELETE request to the document URL
    }

    private function deleteUserByUid(string $uid): array {
        // Private method to delete a user record from Firestore based on their UID
        $user = $this->getUserByUid($uid); // Retrieve the user's document based on the UID
        if ($user && isset($user['uid'])) {
            // Check if a user was found
            return $this->deleteUser($user['uid']); // Delete the user using their document ID (which is assumed to be the UID)
        } else {
            return ['error' => 'User not found with UID: ' . $uid]; // Return an error if no user was found with the given UID
        }
    }

    private function checkUserExistsByUid(string $uid): bool {
        // Private method to check if a user exists in Firestore based on their UID
        $user = $this->getUserByUid($uid); // Call the getUserByUid method to retrieve the user
        return $user !== null; // Return true if getUserByUid returns a non-null value (user exists), false otherwise
    }

    private function _prepareFields(array $data): array {
        // Private method to convert a PHP array into the Firestore fields format
        $fields = []; // Initialize an empty array to store Firestore fields
        foreach ($data as $key => $value) {
            // Iterate through each key-value pair in the input data array
            if (is_string($value)) {
                // If the value is a string
                $fields[$key] = ['stringValue' => $value]; // Format it as a Firestore string value
            } elseif (is_bool($value)) {
                // If the value is a boolean
                $fields[$key] = ['booleanValue' => $value]; // Format it as a Firestore boolean value
            } elseif (is_null($value)) {
                // If the value is null
                $fields[$key] = ['nullValue' => null]; // Format it as a Firestore null value
            } elseif (is_int($value)) {
                // If the value is an integer
                $fields[$key] = ['integerValue' => (string)$value]; // Format it as a Firestore integer value (needs to be a string)
            } elseif (is_float($value)) {
                // If the value is a float
                $fields[$key] = ['doubleValue' => $value]; // Format it as a Firestore double value
            } elseif (is_array($value)) {
                // If the value is an array
                $fields[$key] = ['arrayValue' => ['values' => $this->_prepareArrayValues($value)]]; // Format it as a Firestore's arrayValue
            } elseif (is_object($value)) {
                 // If the value is a map
                $fields[$key] = ['mapValue' => ['fields' => $this->_prepareFields((array)$value)]]; // Format it as a Firestore's mapValue
            }
        }
        return $fields; // Return the array of Firestore formatted fields
    }

    private function _prepareArrayValues(array $array): array {
        $values = [];
        foreach ($array as $item) {
            if (is_string($item)) {
                $values[] = ['stringValue' => $item];
            } elseif (is_bool($item)) {
                $values[] = ['booleanValue' => $item];
            } elseif (is_null($item)) {
                $values[] = ['nullValue' => null];
            } elseif (is_int($item)) {
                $values[] = ['integerValue' => (string)$item];
            } elseif (is_float($item)) {
                $values[] = ['doubleValue' => $item];
            } elseif (is_array($item)) {
                $values[] = ['arrayValue' => ['values' => $this->_prepareArrayValues($item)]];
            } elseif (is_object($item)) {
                $values[] = ['mapValue' => ['fields' => $this->_prepareFields((array)$item)]];
            }
        }
        return $values;
    }

    private function _parseFirestoreDocument(array $document): array {
        // Private method to parse a Firestore document (for lists) into a simpler PHP array
        $output = []; // Initialize an empty array for the output
        $nameParts = explode('/', $document['name']); // Split the document name by '/' to extract the document ID
        $output['uid'] = end($nameParts); // The last part of the name is the document ID, which we use as the user's UID
        if (isset($document['fields'])) {
            // Check if the document has a 'fields' key
            foreach ($document['fields'] as $key => $valueWrapper) {
                // Iterate through each field in the document
                $type = key($valueWrapper); // Get the data type of the field (e.g., stringValue, booleanValue)
                $value = $valueWrapper[$type]; // Get the actual value of the field
                // Map Firestore types to PHP types
                $output[$key] = match ($type) {
                    'stringValue' => $value,
                    'booleanValue' => (bool)$value,
                    default => $value,
                };
            }
        }
        return $output; // Return the simplified user data array
    }

    private function _parseFirestoreDocumentSingle(array $document): array {
        // Private method to parse a single Firestore document into a simpler PHP array
        $output = []; // Initialize an empty array for the output
        if (isset($document['fields'])) {
            // Check if the document has a 'fields' key
            foreach ($document['fields'] as $key => $valueWrapper) {
                // Iterate through each field in the document
                $type = key($valueWrapper); // Get the data type of the field
                $value = $valueWrapper[$type]; // Get the actual value
                $output[$key] = match ($type) {
                    'stringValue' => $value,
                    'booleanValue' => (bool)$value,
                    default => $value,
                };
            }
        }
        return $output; // Return the simplified user data array
    }
}

// Endpoint handler block, routes requests based on PATH_INFO and HTTP method
if (isset($_SERVER['PATH_INFO'])) {
    $pathInfo = trim($_SERVER['PATH_INFO'], '/'); // Get the path information from the URL and trim leading/trailing slashes
    $segments = explode('/', $pathInfo); // Split the path information into an array of segments
    $method = $_SERVER['REQUEST_METHOD']; // Get the HTTP request method (GET, POST, PUT, DELETE, etc.)
    $users = new Users(); // Create an instance of the Users class
    header('Content-Type: application/json'); // Set the Content-Type header to application/json for API responses

    if ($method === 'GET') {
        if (isset($segments[0]) && $segments[0] === 'user' && isset($segments[1])) {
            // Handle GET request for /user/{id} to retrieve a user by their document ID
            echo json_encode($users->getUser($segments[1]));
        } elseif (isset($segments[0]) && $segments[0] === 'uid' && isset($segments[1])) {
            // Handle GET request for /uid/{id} to retrieve a user by their Firebase UID
            echo json_encode($users->getUserByUid($segments[1]));
        } elseif (isset($segments[0]) && $segments[0] === 'all') {
            // Handle GET request for /all to retrieve all user records
            echo json_encode($users->displayAll());
        } else {
            http_response_code(400); // Set the HTTP response code to 400 (Bad Request)
            echo json_encode(['error' => 'Invalid GET request']); // Return a JSON error message for invalid GET requests
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true); // Decode the JSON data sent in the request body
        if (isset($segments[0]) && $segments[0] === 'uid') {
            // Handle POST request to /uid to insert a new user record
            echo json_encode($users->insertUser($data));
        }
        // Handle POST request to /create for user registration (checks for existing user)
        elseif (isset($segments[0]) && $segments[0] === 'create') {
            $data = json_decode(file_get_contents('php://input'), true); // Decode the JSON data sent in the request body
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
            echo json_encode($users->updateUser($segments[1], $data));
        } else {
            http_response_code(400); // Set the HTTP response code to 400 (Bad Request)
            echo json_encode(['error' => 'Invalid PUT/PATCH request']); // Return a JSON error message for invalid PUT or PATCH requests
        }
    } elseif ($method === 'DELETE') {
        // Handle DELETE request for /user/{id} to delete a user record by document ID
        if (isset($segments[0]) && $segments[0] === 'user' && isset($segments[1])) {
            echo json_encode($users->deleteUser($segments[1]));
        } elseif (isset($segments[0]) && $segments[0] === 'uid' && isset($segments[1])) {
            // Handle DELETE request for /uid/{id} to delete a user record by Firebase UID
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