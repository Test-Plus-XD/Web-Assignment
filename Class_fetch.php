<?php
ob_start(); // Output Buffering
// Enable error reporting for debugging purposes.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include the Database class which creates the PDO connection.
require_once "Class_db_connect.php";

// Instantiate the Database class.
$DB = new Database();
// Obtain the PDO connection using the getConnection() method.
$conn = $DB->getConnection();

// Determine whether the request comes from JavaScript (AJAX).
// Check if the HTTP_X_REQUESTED_WITH header equals 'XMLHttpRequest'.
$isAJAX = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
// Instantiate the Fetch class with the PDO connection.

// This class encapsulates methods to retrieve product information from the database.
class Fetch {
    private $conn;                                 // PDO connection object
    private $tbname = "tb_products";               // Table name for products

    // Constructor: initializes the class with a PDO connection.
    // @param PDO $conn A valid PDO connection object.
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // This method is intended for AJAX (JavaScript) requests.
    // It reads the JSON input from the request body, decodes it, checks the 'action' parameter, and calls the appropriate method.
    public function handleRequest() {
        // Read and decode the JSON input from the request.
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? null;
        // If no action parameter is provided, return an error.
        if (!$action) {
            echo json_encode(["error" => "No action parameter provided"]);
            exit;
        }
        // Decide which method to call based on the 'action' parameter.
        // Parse all response into JSON.
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
        exit; // Stop execution after outputting JSON.
    }

    // Retrieves products for the cart based on an array of product IDs.
    // @param array $input The decoded JSON input containing product IDs.
    private function cartProducts($input) {
        $productIds = $input['productIds'] ?? [];
        if (empty($productIds)) {
            return ["error" => "No product IDs provided"];
        }

        // Create a string of placeholders (?, ?, ...) based on the number of product IDs.
        $placeholders = implode(",", array_fill(0, count($productIds), "?"));
        $sql = "SELECT product_id, cardTitle, itemPrice AS price, imageSrc, imageAlt, (itemPrice = 0) AS isFree 
                FROM {$this->tbname} 
                WHERE product_id IN ($placeholders)";

        $stmt = $this->conn->prepare($sql); // Prepare and execute the statement; PDO automatically binds the array values.
        $stmt->execute($productIds); // Fetch all matching rows as an associative array.
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Return the result as a JSON response.
    }

    // Retrieves detailed information for a specific product.
    // Modified to accept an 'id' parameter (product id) instead of a 'page' parameter.
    // @param array $input The decoded JSON input containing the 'id' parameter.
    private function productDetails($input) {
        // Use 'id' instead of 'page'
        $id = $input['id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return ["error" => "No product ID provided"];
        }
        // Updated query: use product_id for matching.
        $sql = "SELECT p.product_id AS pid, p.cardTitle AS name, p.itemPrice AS price, 
                    (p.itemPrice = 0) AS isFree, 
                    COALESCE(o.user_id IS NOT NULL, 0) AS isOwned
                    FROM tb_products p
                    LEFT JOIN tb_owned_products o ON p.product_id = o.product_id AND o.user_id = ?
                    WHERE p.product_id = ?";
        $stmt = $this->conn->prepare($sql); // Prepare the query.
        $stmt->execute([$userId, $id]); // Execute with the user ID and product ID.
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        // Return the product details as JSON, or an error if not found.
        return $product ? $product : ["error" => "No product found"];
    }

    // Retrieves all products from the database in an array.
    public function allProducts() {
        // SQL query to fetch all product details, including the new isDigital field.
        $sql = "SELECT * FROM {$this->tbname}";
        $stmt = $this->conn->query($sql); // Execute the query using PDO's query() method.
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all rows as an associative array.
        if (!$rows) {
            return [];
        }
        $products = array();
        foreach ($rows as $row) {
            $products[] = array(
                'product_id' => $row['product_id'],         // Product primary key from tb_products
                'id'         => $row['cardID'],             // Unique identifier for the product card
                'name'       => $row['cardTitle'],          // Product title
                'price'      => $row['itemPrice'],          // Product price
                'isFree'     => $row['itemPrice'] == 0,      // True if product is free
                'cardText'   => $row['cardText'],            // Product description text
                'imageSrc'   => $row['imageSrc'],            // Source URL for the product image
                'imageAlt'   => $row['imageAlt'],            // Alternative text for the product image
                'isDigital'  => $row['isDigital'] == 1       // Digital flag as boolean (true if 1)
            );
        }
        return $products;
    }

    // Retrieves the products owned by a given user.
    // Performs an INNER JOIN between tb_owned_products and tb_products.
    public function library($user_id) {
        // SQL query: join tb_owned_products (alias u) with tb_products (alias p)
        $sql = "SELECT p.product_id, p.cardID, p.cardTitle, p.cardText, p.itemPrice, p.imageSrc, p.imageAlt, p.isDigital
                FROM tb_owned_products u
                INNER JOIN {$this->tbname} p ON u.product_id = p.product_id
                WHERE u.user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $products;
    }

    // Updates the library for the logged-in user by inserting the product if not already owned,
    // then returns a JSON response indicating success or failure.
    public function libraryUpdate() {
        // Set the table name for owned products.
        $tbname = "tb_owned_products";
        // Read and decode JSON input to retrieve the productId.
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = $input['productId'] ?? null;
        $userId = $_SESSION["user_id"] ?? null;
        $sessionId = session_id() ?? null;
        // If user or product data is missing, return an error.
        if (!$userId || !$productId) {
            return ["success" => false, "message" => "Missing user or product data"];
        }
        // Prepare the SQL statement using INSERT IGNORE.
        $sql = "INSERT IGNORE INTO $tbname (user_id, product_id, session) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ["success" => false, "message" => "Failed to prepare statement"];
        }
        // Execute the statement with bound parameters.
        $result = $stmt->execute([$userId, $productId, $sessionId]);
        if ($result) {
            return ["success" => true];
        } else {
            return ["success" => false, "message" => "Failed to update library"];
        }
    }
}

// For AJAX requests, use handleRequest() to process the incoming action parameter.
if ($isAJAX) {
    // Set the Content-Type header to JSON only for AJAX requests.
    // This ensures that when JavaScript calls this script, it receives JSON.
    header("Content-Type: application/json");
    $fetchInstance = new Fetch($conn);
    $fetchInstance->handleRequest(); // AJAX will receive JSON response
    ob_end_flush(); // Flush buffered output.
    exit; // Prevent further execution
}
ob_end_clean(); // Clear any buffered output.
?>