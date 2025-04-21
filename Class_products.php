<?php
// Include Firestore configuration, constants, and API trait
require_once 'firestore.php';

class Products {
    // Import Firestore REST helpers and parsers
    use FirestoreApiTrait;

    // The Firestore collection for products
    private const COLLECTION_NAME = 'tb_products';

    // Return the collection name (required by the trait)
    protected function getCollectionName(): string {
        return self::COLLECTION_NAME;
    }

    // List all products (returns parsed JSON response).
    public function listProducts(): array {
        // GET /tb_products
        // Example: curl -X GET http://your-domain.com/Class_products.php/all
        $resp = $this->_makeRequest('', 'GET');
        $items = [];
        if (isset($resp['documents'])) {
            foreach ($resp['documents'] as $doc) {
                $items[] = $this->_parseFirestoreDocument($doc);
            }
        }
        return $items;
    }

    // Get a single product by ID (Firestore document ID) (returns parsed JSON response).
    public function getProduct(string $id) {
        $productResponse = $this->_makeRequest($id, 'GET');
        if (isset($productResponse['fields'])) {
            return $this->_parseFirestoreDocument($productResponse);
        }
        return $productResponse;
    }

    // Insert a new product (sends raw JSON data).
    public function insertProduct(array $data) {
        return $this->_makeRequest('', 'POST', $data);
    }

    // Update an existing product (requires Firestore document ID) (sends raw JSON data).
    public function updateProduct(string $id, array $data) {
        return $this->_makeRequest($id, 'PATCH', $data);
    }

    // Delete a product (requires Firestore document ID).
    public function deleteProduct(string $id) {
        return $this->_makeRequest($id, 'DELETE');
    }


}

// Endpoint handler block for Products, routes requests based on PATH_INFO and HTTP method to output JSON
if (isset($_SERVER['PATH_INFO'])) {
    $pathInfo = trim($_SERVER['PATH_INFO'], '/'); // Get the path information from the URL and trim leading/trailing slashes
    $segments = explode('/', $pathInfo); // Split the path information into an array of segments
    $method = $_SERVER['REQUEST_METHOD']; // Get the HTTP request method (GET, POST, PUT, DELETE, etc.)
    $products = new Products(); // Create an instance of the Users class
    header('Content-Type: application/json'); // Set the Content-Type header to application/json for API responses

    if ($method === 'GET') {
        if (isset($segments[0]) && $segments[0] === 'product' && isset($segments[1])) {
            // Example cURL command: curl -X GET http://your-domain.com/Class_products.php/product/some_product_id
            $productId = $segments[1];
            $product = $products->getProduct($productId);
            echo json_encode($product);
        } elseif (isset($segments[0]) && $segments[0] === 'all') {
            // Example cURL command: curl -X GET http://your-domain.com/Class_products.php/all
            $allProducts = $products->listProducts();
            echo json_encode($allProducts);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid GET request']);
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($segments[0]) && $segments[0] === 'product') {
            // Example cURL command: curl -X POST -H "Content-Type: application/json" -d '{"name": "New Product", "price": 25.99}' http://your-domain.com/Class_products.php/product
            $insertResult = $products->insertProduct($data);
            echo json_encode($insertResult); // Output the full JSON response
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid POST request']);
        }
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        if (isset($segments[0]) && $segments[0] === 'product' && isset($segments[1])) {
            // Example cURL command (PUT): curl -X PUT -H "Content-Type: application/json" -d '{"price": 29.99}' http://your-domain.com/Class_products.php/product/some_product_id
            // Example cURL command (PATCH): curl -X PATCH -H "Content-Type: application/json" -d '{"price": 29.99}' http://your-domain.com/Class_products.php/product/some_product_id
            $productId = $segments[1];
            $data = json_decode(file_get_contents('php://input'), true);
            $updateResult = $products->updateProduct($productId, $data);
            echo json_encode($updateResult); // Output the full JSON response
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid PUT/PATCH request']);
        }
    } elseif ($method === 'DELETE') {
        if (isset($segments[0]) && $segments[0] === 'product' && isset($segments[1])) {
            // Example cURL command: curl -X DELETE http://your-domain.com/Class_products.php/product/some_product_id
            $productId = $segments[1];
            $deleteResult = $products->deleteProduct($productId);
            echo json_encode($deleteResult); // Output the full JSON response
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid DELETE request']);
        }
    } else {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method not allowed']);
    }
}
?>