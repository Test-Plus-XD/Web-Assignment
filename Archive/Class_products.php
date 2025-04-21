<?php
require_once 'firestore.php';
class Products {
    private const COLLECTION_NAME = 'tb_products';
    private string $baseUrl;
    private string $apiKey;

    public function __construct() {
        $this->baseUrl = FIRESTORE_API_BASE_URL . '/' . self::COLLECTION_NAME;
        $this->apiKey = FIREBASE_API_KEY;
    }

    // Helper function to make Firestore REST API requests.
    private function _makeRequest(string $endpoint = '', string $method = 'GET', ?array $data = null): ?array {
        $url = $this->baseUrl . '/' . $endpoint . '?key=' . $this->apiKey;
        $ch = curl_init($url);

        setFirestoreCurlOptions($ch, $method); // Set common cURL options

        if ($data) {
            $jsonData = json_encode($data); // Send raw data as JSON
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            error_log("Firestore API cURL Error for Products: " . $curlError);
            return ['error' => 'cURL Error', 'details' => $curlError];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Firestore API HTTP Error {$httpCode} for Products: " . $response);
            $decodedResponse = json_decode($response, true);
            return $decodedResponse ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response];
        }

        return json_decode($response, true);
    }

    // Display all products (returns parsed JSON response).
    public function display() {
        $allProductsResponse = $this->_makeRequest('', 'GET');
        $allProducts = [];
        if (isset($allProductsResponse['documents'])) {
            foreach ($allProductsResponse['documents'] as $document) {
                $allProducts[] = $this->_parseFirestoreDocument($document);
            }
        }
        return $allProducts;
    }

    // Get a single product by ID (Firestore document ID) (returns parsed JSON response).
    public function getProduct(string $id) {
        $productResponse = $this->_makeRequest($id, 'GET');
        if (isset($productResponse['fields'])) {
            return $this->_parseFirestoreDocumentSingle($productResponse);
        }
        return $productResponse;
    }

    // Insert a new product (sends raw JSON data).
    public function insertProduct(array $data) {
        return $this->_makeRequest('', 'POST', ['fields' => $this->_prepareFields($data)]);
    }

    // Update an existing product (requires Firestore document ID) (sends raw JSON data).
    public function updateProduct(string $id, array $data) {
        return $this->_makeRequest($id, 'PATCH', ['fields' => $this->_prepareFields($data)]);
    }

    // Delete a product (requires Firestore document ID).
    public function deleteProduct(string $id) {
        return $this->_makeRequest($id, 'DELETE');
    }

    // Helper function to prepare data for Firestore fields (remains the same for outgoing data).
    private function _prepareFields(array $data): array {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $fields[$key] = ['stringValue' => $value];
            } elseif (is_int($value)) {
                $fields[$key] = ['integerValue' => $value];
            } elseif (is_float($value)) {
                $fields[$key] = ['doubleValue' => $value];
            } elseif (is_bool($value)) {
                $fields[$key] = ['booleanValue' => $value];
            } elseif (is_null($value)) {
                $fields[$key] = ['nullValue' => null];
            } elseif (is_array($value)) {
                $arrayValues = [];
                foreach ($value as $item) {
                    if (is_string($item)) $arrayValues[] = ['stringValue' => $item];
                    elseif (is_int($item)) $arrayValues[] = ['integerValue' => $item];
                    elseif (is_float($item)) $arrayValues[] = ['doubleValue' => $item];
                    elseif (is_bool($item)) $arrayValues[] = ['booleanValue' => $item];
                    elseif (is_null($item)) $arrayValues[] = ['nullValue' => null];
                }
                $fields[$key] = ['arrayValue' => ['values' => $arrayValues]];
            }
        }
        return $fields;
    }

    // Private method to convert a Firestore document structure to a simpler array (for lists).
    private function _parseFirestoreDocument(array $document): array {
        $output = [];
        $nameParts = explode('/', $document['name']);
        $output['product_id'] = end($nameParts); // Using Firestore document ID as product_id
        if (isset($document['fields'])) {
            foreach ($document['fields'] as $key => $valueWrapper) {
                $type = key($valueWrapper);
                $output[$key] = $valueWrapper[$type] ?? null;
                if (isset($output[$key]['stringValue'])) $output[$key] = $output[$key]['stringValue'];
                elseif (isset($output[$key]['integerValue'])) $output[$key] = (int)$output[$key]['integerValue'];
                elseif (isset($output[$key]['doubleValue'])) $output[$key] = (float)$output[$key]['doubleValue'];
                elseif (isset($output[$key]['booleanValue'])) $output[$key] = (bool)$output[$key]['booleanValue'];
                // Add more type handling as needed.
            }
        }
        return $output;
    }

    // Private method to convert a Firestore document structure to a simpler array (single document).
    private function _parseFirestoreDocumentSingle(array $document): array {
        $output = [];
        if (isset($document['fields'])) {
            foreach ($document['fields'] as $key => $valueWrapper) {
                $type = key($valueWrapper);
                $output[$key] = $valueWrapper[$type] ?? null;
                if (isset($output[$key]['stringValue'])) $output[$key] = $output[$key]['stringValue'];
                elseif (isset($output[$key]['integerValue'])) $output[$key] = (int)$output[$key]['integerValue'];
                elseif (isset($output[$key]['doubleValue'])) $output[$key] = (float)$output[$key]['doubleValue'];
                elseif (isset($output[$key]['booleanValue'])) $output[$key] = (bool)$output[$key]['booleanValue'];
                // Add more type handling as needed.
            }
        }
        return $output;
    }
}

// Handling URL requests to output JSON
if (isset($_SERVER['PATH_INFO'])) {
    $pathInfo = trim($_SERVER['PATH_INFO'], '/');
    $segments = explode('/', $pathInfo);
    $method = $_SERVER['REQUEST_METHOD'];
    $products = new Products();

    header('Content-Type: application/json');

    if ($method === 'GET') {
        if (isset($segments[0]) && $segments[0] === 'product' && isset($segments[1])) {
            $productId = $segments[1];
            $product = $products->getProduct($productId);
            echo json_encode($product);
        } elseif (isset($segments[0]) && $segments[0] === 'all') {
            $allProducts = $products->display();
            echo json_encode($allProducts);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid GET request']);
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($segments[0]) && $segments[0] === 'product') {
            $insertResult = $products->insertProduct($data);
            echo json_encode($insertResult); // Output the full JSON response
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid POST request']);
        }
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        if (isset($segments[0]) && $segments[0] === 'product' && isset($segments[1])) {
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