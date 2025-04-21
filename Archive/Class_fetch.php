<?php
ob_start(); // Start output buffering
// Enable error reporting for debugging purposes.
ini_set('display_errors', 1); // Display errors during development
ini_set('display_startup_errors', 1); // Display startup errors
error_reporting(E_ALL); // Report all errors

session_start(); // Start the session to hold user login info, etc.

//--------------------------------------------------------------------------
// Define Firestore configuration constants
//-------------------------------------------------------------------------- 
define('FIRESTORE_PROJECT_ID', 'web-assignment-4237d'); // Your Firebase project ID
define('FIRESTORE_DATABASE', '(default)'); // Default Firestore database name (change if necessary)
define('FIRESTORE_API_BASE_URL', 'https://firestore.googleapis.com/v1'); // Base URL for Firestore REST API
define('FIREBASE_API_KEY', 'AIzaSyCQDJLKGSEzBn3HMqe7c3KHp1iUapZOYm4'); // Your Firebase API key

//--------------------------------------------------------------------------
// Function: getAccessToken()
// Purpose: Return an OAuth access token for Firestore requests.
// Note: In a production system, you would obtain this via a service account or a suitable method.
// For testing purposes, you can return a token that has been generated manually.
function getAccessToken() {
    // IMPORTANT: Replace the following hardcoded token with your token retrieval logic.
    return 'YOUR_ACCESS_TOKEN';
}

//--------------------------------------------------------------------------
// Function: firestoreRequest()
// Purpose: Centralized function to perform REST API requests to Firestore.
// Parameters:
//   $method - HTTP method (GET, POST, PATCH, DELETE)
//   $url    - Full request URL
//   $data   - An array of data to be JSON-encoded and sent as the request body (optional)
// Returns: Decoded JSON response from Firestore.
function firestoreRequest(string $method, string $url, ?array $data = null): ?array {
    $ch = curl_init(); // Initialize cURL session
    // Set HTTP headers; include Authorization for OAuth if available
    $headers = [
        'Content-Type: application/json; charset=UTF-8',
        'Authorization: Bearer ' . getAccessToken()
    ];
    curl_setopt($ch, CURLOPT_URL, $url); // Set request URL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as string
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); // Set HTTP method

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Set headers

    // If data is provided, encode it as JSON using unescaped Unicode for clarity
    if ($data) {
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Attach JSON payload
        error_log("Firestore API Payload: " . $jsonData); // Log payload for debugging
    }
    $response = curl_exec($ch); // Execute the cURL request
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP response code
    $curlError = curl_error($ch); // Capture any cURL error
    curl_close($ch); // Close cURL session

    if ($curlError) {
        error_log("Firestore API cURL Error: " . $curlError);
        return ['error' => 'cURL Error', 'details' => $curlError];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("Firestore API HTTP Error {$httpCode}: " . $response);
        return json_decode($response, true) ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response];
    }
    return json_decode($response, true); // Return decoded response
}

//--------------------------------------------------------------------------
// Class: Fetch
// Purpose: Encapsulate methods to retrieve product information from Firestore.
//          This replaces the PDO-based database access with Firestore REST API calls.
//--------------------------------------------------------------------------
class Fetch {
    // Base URL for Firestore collection (e.g. tb_products)
    private $productsUrl;
    // Base URL for Firestore owned products collection (tb_owned_products)
    private $ownedProductsUrl;

    // Constructor: initializes the base URLs using constants defined above.
    public function __construct() {
        // Construct URL for tb_products collection. Note: ensure your database structure is correct.
        $this->productsUrl = FIRESTORE_API_BASE_URL . '/projects/' . FIRESTORE_PROJECT_ID .
                             '/databases/' . FIRESTORE_DATABASE . '/documents/tb_products';
        // Construct URL for tb_owned_products collection.
        $this->ownedProductsUrl = FIRESTORE_API_BASE_URL . '/projects/' . FIRESTORE_PROJECT_ID .
                                  '/databases/' . FIRESTORE_DATABASE . '/documents/tb_owned_products';
    }

    //----------------------------------------------------------------------
    // Method: handleRequest()
    // Purpose: Reads the JSON input from the HTTP request, checks the 'action'
    //          parameter, and calls the appropriate method. Outputs JSON response.
    //----------------------------------------------------------------------
    public function handleRequest() {
        // Read raw JSON from the request body and decode into an array
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? null; // Extract the 'action' parameter, if set
        if (!$action) {
            echo json_encode(["error" => "No action parameter provided"]);
            exit;
        }
        // Decide which method to invoke based on 'action'
        switch ($action) {
            case 'cart':
                echo json_encode($this->cartProducts($input));
                break;
            case 'product':
                echo json_encode($this->productDetails($input));
                break;
            case 'libraryUpdate':
                echo json_encode($this->libraryUpdate());
                break;
            default:
                echo json_encode($this->allProducts());
                break;
        }
        exit; // Stop execution after outputting JSON
    }

    //----------------------------------------------------------------------
    // Method: cartProducts()
    // Purpose: Retrieve product details for products listed in the 'productIds'
    //          array, for use in a shopping cart.
    // Parameter: $input - Array with key 'productIds' listing product IDs.
    // Returns: Array of product details in JSON-compatible format.
    //----------------------------------------------------------------------
    private function cartProducts($input) {
        // Extract product IDs from input; if empty, return error message.
        $productIds = $input['productIds'] ?? [];
        if (empty($productIds)) {
            return ["error" => "No product IDs provided"];
        }
        $results = [];
        // Iterate over each product ID and fetch its details using the GET request
        foreach ($productIds as $pid) {
            $docUrl = $this->productsUrl . '/' . $pid . '?key=' . FIREBASE_API_KEY;
            $doc = firestoreRequest('GET', $docUrl); // GET the document from Firestore
            if (isset($doc['fields'])) {
                $fields = $doc['fields'];
                // Build a simplified product array with required fields
                $results[] = [
                    'product_id' => $pid,
                    'cardTitle'  => $fields['cardTitle']['stringValue'] ?? '',
                    'price'      => isset($fields['itemPrice']['doubleValue']) ? (float)$fields['itemPrice']['doubleValue'] 
                                    : (isset($fields['itemPrice']['integerValue']) ? (int)$fields['itemPrice']['integerValue'] : 0),
                    'imageSrc'   => $fields['imageSrc']['stringValue'] ?? '',
                    'imageAlt'   => $fields['imageAlt']['stringValue'] ?? '',
                    'isFree'     => (isset($fields['itemPrice']['doubleValue']) ? ((float)$fields['itemPrice']['doubleValue'] == 0) 
                                    : (isset($fields['itemPrice']['integerValue']) && ((int)$fields['itemPrice']['integerValue'] == 0)))
                ];
            }
        }
        return $results;
    }

    //----------------------------------------------------------------------
    // Method: productDetails()
    // Purpose: Retrieve detailed information for a specific product.
    // Parameters: $input - Array containing an 'id' parameter (the product ID).
    // Returns: Array of product details or an error message.
    //----------------------------------------------------------------------
    private function productDetails($input) {
        $id = $input['id'] ?? null; // Get product id from input
        $userId = $_SESSION['user_id'] ?? null; // Get user id from the session
        if (!$id) {
            return ["error" => "No product ID provided"];
        }
        // Build the URL to get the product document
        $docUrl = $this->productsUrl . '/' . $id . '?key=' . FIREBASE_API_KEY;
        $doc = firestoreRequest('GET', $docUrl); // GET product details from Firestore
        if (!isset($doc['fields'])) {
            return ["error" => "No product found"];
        }
        $fields = $doc['fields'];

        // Build a structured query to check if the user owns this product
        $structuredQuery = [
            'from' => [['collectionId' => 'tb_owned_products']],
            'where' => [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => [
                        [
                            'fieldFilter' => [
                                'field' => ['fieldPath' => 'user_id'],
                                'op' => 'EQUAL',
                                'value' => ['stringValue' => $userId]
                            ]
                        ],
                        [
                            'fieldFilter' => [
                                'field' => ['fieldPath' => 'product_id'],
                                'op' => 'EQUAL',
                                'value' => ['stringValue' => $id]
                            ]
                        ]
                    ]
                ]
            ],
            'limit' => 1
        ];
        // Build URL for running a query on the tb_owned_products collection
        $queryUrl = FIRESTORE_API_BASE_URL . '/projects/' . FIRESTORE_PROJECT_ID . '/databases/' . FIRESTORE_DATABASE . '/documents:runQuery?key=' . FIREBASE_API_KEY;
        $ownedQueryResponse = firestoreRequest('POST', $queryUrl, ['structuredQuery' => $structuredQuery]);
        // Determine if the product is owned based on query results
        $isOwned = isset($ownedQueryResponse[0]['document']);

        return [
            "pid"     => $id,
            "name"    => $fields['cardTitle']['stringValue'] ?? '',
            "price"   => isset($fields['itemPrice']['doubleValue']) ? (float)$fields['itemPrice']['doubleValue'] 
                         : (isset($fields['itemPrice']['integerValue']) ? (int)$fields['itemPrice']['integerValue'] : 0),
            "isFree"  => isset($fields['itemPrice']['doubleValue']) ? ((float)$fields['itemPrice']['doubleValue'] == 0) 
                         : (isset($fields['itemPrice']['integerValue']) && ((int)$fields['itemPrice']['integerValue'] == 0)),
            "isOwned" => $isOwned
        ];
    }

    //----------------------------------------------------------------------
    // Method: allProducts()
    // Purpose: Retrieve all products from Firestore's tb_products collection.
    // Returns: Array of products with their key fields.
    //----------------------------------------------------------------------
    public function allProducts() {
        $url = $this->productsUrl . '?key=' . FIREBASE_API_KEY; // URL to get all product documents
        $response = firestoreRequest('GET', $url); // Execute GET request
        if (!isset($response['documents'])) {
            return [];
        }
        $products = [];
        // Iterate over each document returned from Firestore
        foreach ($response['documents'] as $doc) {
            // Extract document ID from its full path
            $docId = basename($doc['name']);
            $f = $doc['fields'];
            $products[] = [
                'product_id' => $docId, // Use Firestore document ID
                'id'         => $f['cardID']['stringValue'] ?? '',
                'name'       => $f['cardTitle']['stringValue'] ?? '',
                'price'      => isset($f['itemPrice']['doubleValue']) ? (float)$f['itemPrice']['doubleValue'] 
                                : (isset($f['itemPrice']['integerValue']) ? (int)$f['itemPrice']['integerValue'] : 0),
                'isFree'     => (isset($f['itemPrice']['doubleValue']) ? ((float)$f['itemPrice']['doubleValue'] == 0) 
                                : (isset($f['itemPrice']['integerValue']) && ((int)$f['itemPrice']['integerValue'] == 0))),
                'cardText'   => $f['cardText']['stringValue'] ?? '',
                'imageSrc'   => $f['imageSrc']['stringValue'] ?? '',
                'imageAlt'   => $f['imageAlt']['stringValue'] ?? '',
                'isDigital'  => isset($f['isDigital']['booleanValue']) ? (bool)$f['isDigital']['booleanValue'] : false
            ];
        }
        return $products;
    }

    //----------------------------------------------------------------------
    // Method: libraryUpdate()
    // Purpose: Updates the library for the logged-in user by inserting a new document
    //          into the tb_owned_products collection with the user's Firestore UID, 
    //          the product's Firestore document ID, and the session ID.
    // Returns: JSON response indicating success or failure.
    //----------------------------------------------------------------------
    public function libraryUpdate() {
        $input = json_decode(file_get_contents('php://input'), true); // Decode JSON input
        $productId = $input['productId'] ?? null; // Get the product ID from input
        $userId = $_SESSION["user_id"] ?? null; // Get logged-in user's Firestore UID from the session
        $sessionId = session_id(); // Use session ID as a record for purchase session

        if (!$userId || !$productId) {
            return ["success" => false, "message" => "Missing user or product data"];
        }
        // Prepare the Firestore document data for tb_owned_products
        $docData = [
            "fields" => [
                // Reference type can be used if you want to store a Firestore document reference.
                // Here we store them as plain strings for simplicity.
                "user_id"    => ["stringValue" => $userId],
                "product_id" => ["stringValue" => $productId],
                "session"    => ["stringValue" => $sessionId]
            ]
        ];
        // URL to insert a new document into tb_owned_products
        $url = $this->ownedProductsUrl . '?key=' . FIREBASE_API_KEY;
        $response = firestoreRequest('POST', $url, $docData); // Execute POST request
        if (isset($response['name'])) {
            return ["success" => true];
        } else {
            return ["success" => false, "message" => "Failed to update library", "errorDetails" => $response];
        }
    }
}

//--------------------------------------------------------------------------
// Determine if the request is from an AJAX call by checking the HTTP_X_REQUESTED_WITH header.
$isAJAX = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
         $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

// If this is an AJAX request, set the Content-Type header to application/json,
// instantiate the Fetch class, and call its handleRequest() method.
if ($isAJAX) {
    header("Content-Type: application/json"); // Set JSON response header
    $fetchInstance = new Fetch(); // Create an instance of the Fetch class
    $fetchInstance->handleRequest(); // Process the request and output JSON
    ob_end_flush(); // Flush buffered output
    exit; // Terminate script execution
}
ob_end_clean(); // Clear any buffered output if not an AJAX request
?>