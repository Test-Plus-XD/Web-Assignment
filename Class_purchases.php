<?php
// Include Firestore API trait and configuration constants
require_once 'firestore.php';

class Purchases {
    // Bring in Firestore REST helper methods and parsers
    use FirestoreApiTrait;

    public function containsBOM(string $content): bool {
        // BOM in UTF-8 is: 0xEF,0xBB,0xBF
        return substr($content, 0, 3) === "\xEF\xBB\xBF";
    }

    // Firestore collection name for purchases
    private const COLLECTION_NAME = 'tb_purchases';

    // Return the collection name (required by the trait)
    protected function getCollectionName(): string {
        return self::COLLECTION_NAME;
    }

    // Create a new purchase record
    public function createPurchase(array $data): array {
        // POST to /tb_purchases with provided data
        $response = $this->_makeRequest('', 'POST', $data);
        return isset($response['fields']) ? $this->_parseFirestoreDocument($response) : $response;
    }

    // Retrieve a single purchase by its document ID
    public function getPurchase(string $purchaseId): array {
        // GET from /tb_purchases/{purchaseId}
        $response = $this->_makeRequest($purchaseId, 'GET');
        // Check if the response contains document fields before parsing
        if (isset($response['fields'])) {
            return $this->_parseFirestoreDocument($response);
        }
        // If no fields or an error, return the raw response
        return $response;
    }

    // List all purchase documents in the collection
    public function listPurchases(): array {
        // GET from /tb_purchases to list all documents
        $response  = $this->_makeRequest('', 'GET');
        $purchases = [];
        // Check if the response contains 'documents' array
        if (isset($response['documents'])) {
            foreach ($response['documents'] as $document) {
                // Parse each document returned by the list operation
                $purchases[] = $this->_parseFirestoreDocument($document);
            }
        }
        // Return the array of parsed purchases or an empty array/error if none found or error occurred
        return $purchases;
    }

    // Update an existing purchase document
    public function updatePurchase(string $purchaseId, array $data): array {
        // PATCH to /tb_purchases/{purchaseId} with updated data
        $response = $this->_makeRequest($purchaseId, 'PATCH', $data);
        return isset($response['fields']) ? $this->_parseFirestoreDocument($response) : $response;
    }

    // Delete a purchase document by ID
    public function deletePurchase(string $purchaseId): array {
        // DELETE at /tb_purchases/{purchaseId}
        return $this->_makeRequest($purchaseId, 'DELETE');
    }

    // Query purchases by product_id and uid using runQuery
    public function getPurchasesByProductAndUid(string $productId, string $uid): array {
        // 1) Build the runQuery URL (collection???level)
        $url = FIRESTORE_API_BASE_URL . ':runQuery?key=' . $this->apiKey;
        $ch  = curl_init($url);

        // 2) Parent path + structuredQuery payload
        $queryData = [
            'structuredQuery' => [
                'from' => [['collectionId' => self::COLLECTION_NAME]],
                'where' => [
                    'compositeFilter' => [
                        'op'      => 'AND',
                        'filters' => [
                            [
                                'fieldFilter' => [
                                    'field' => ['fieldPath' => 'product_id'],
                                    'op'    => 'EQUAL',
                                    'value' => ['stringValue' => $productId],
                                ]
                            ],
                            [
                                'fieldFilter' => [
                                    'field' => ['fieldPath' => 'uid'],
                                    'op'    => 'EQUAL',
                                    'value' => ['stringValue' => $uid],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // 3) Send it:
        setFirestoreCurlOptions($ch, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));

        $response = curl_exec($ch);
        if ($this->containsBOM($response)) echo "BOM detected at start of response!";
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['error'=>'cURL Error','details'=>$curlError];
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            $decoded = json_decode($response, true);
            return $decoded ?: ['error'=>"HTTP Error {$httpCode}",'details'=>$response];
        }

        // 4) Parse the array of { document: {  } } results:
        $decoded = json_decode($response, true);
        $results = [];
        foreach ($decoded as $row) {
            if (isset($row['document'])) {
                $results[] = $this->_parseFirestoreDocument($row['document']);
            }
        }
        return $results;
    }

    // Query purchases by uid using runQuery.
    public function getPurchasesByUid(string $uid): array {
        // Construct the URL for the :runQuery endpoint.
        // Use $this->baseUrl which already includes the collection name.
        $url = FIRESTORE_API_BASE_URL . ':runQuery?key=' . $this->apiKey;
        $ch  = curl_init($url);

        // Build the structuredQuery JSON payload for filtering by uid.
        $queryData = [
            'structuredQuery' => [
                'from' => [['collectionId' => self::COLLECTION_NAME]], // Specify the collection to query.
                'where' => [
                     'fieldFilter' => [ // Filter by the 'uid' field.
                         'field' => ['fieldPath' => 'uid'],
                         'op'    => 'EQUAL', // Equality operator.
                         'value' => ['stringValue' => $uid], // The UID to filter by, wrapped as stringValue.
                     ],
                ],
                //'orderBy' => [['field' => ['fieldPath' => 'date'], 'direction' => 'DESCENDING']], // Order by data. Needs Composite indexes.
            ],
        ];

        // Apply common Firestore cURL options for POST (runQuery uses POST).
        setFirestoreCurlOptions($ch, 'POST');
        // Attach the JSON-encoded query payload to the request body.
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));

        // Execute the cURL request.
        $response  = curl_exec($ch);
        // Get the HTTP status code.
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Check for cURL errors.
        $curlError = curl_error($ch);
        // Close the cURL session.
        curl_close($ch);

        // Handle cURL errors.
        if ($curlError) {
            error_log("Firestore API cURL Error for getPurchasesByUid: " . $curlError);
            return ['error' => 'cURL Error', 'details' => $curlError];
        }
        // Handle HTTP errors.
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Firestore API HTTP Error {$httpCode} for getPurchasesByUid: " . $response);
            $decoded = json_decode($response, true);
            return $decoded ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response];
        }

        // Decode the JSON response.
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse === null) error_log("JSON decode error: " . json_last_error_msg());
        $results = []; // Array to hold parsed results.
        // The runQuery response is an array of results, where matching documents are nested under 'document'.
        foreach ($decodedResponse as $item) {
            if (isset($item['document'])) {
                // Parse the document structure within each result item.
                $results[] = $this->_parseFirestoreDocument($item['document']);
            }
            // Items without 'document' might be query execution stats, ignore them.
        }
        // Return the array of parsed purchase documents.
        return $results;
    }
}

// Endpoint routing for Purchases API
if (isset($_SERVER['PATH_INFO'])) {
    // Trim and split the request path into segments.
    $pathInfo  = trim($_SERVER['PATH_INFO'], '/');
    $segments  = explode('/', $pathInfo);
    // Get the HTTP request method.
    $method    = $_SERVER['REQUEST_METHOD'];
    // Create an instance of the Purchases class.
    $purchases = new Purchases();
    // Set the Content-Type header to application/json for API responses.
    header('Content-Type: application/json');

    if ($method === 'GET') {
        if (isset($segments[0]) && $segments[0] === 'purchase' && isset($segments[1])) {
            // Handle GET request to /purchase/{id} to retrieve a purchase by ID.
            // Example cURL: curl -X GET http://your-domain.com/Class_purchases.php/purchase/some_purchase_id
            echo json_encode($purchases->getPurchase($segments[1]));
        } elseif (isset($segments[0]) && $segments[0] === 'all') {
            // Handle GET request to /all to list all purchases.
            // Example cURL: curl -X GET http://your-domain.com/Class_purchases.php/all
            echo json_encode($purchases->listPurchases());
        } elseif (isset($segments[0]) && $segments[0] === 'product_uid' && isset($segments[1]) && isset($segments[2])) {
            // Handle GET request to /product_uid/{product_id}/{uid}.
            // Example cURL: curl -X GET http://your-domain.com/Class_purchases.php/product_uid/some_product_id/some_user_id
            echo json_encode($purchases->getPurchasesByProductAndUid($segments[1], $segments[2]));
        } elseif (isset($segments[0]) && $segments[0] === 'uid' && isset($segments[1])) {
             // Handle GET request to /uid/{uid} to list purchases for a specific user ID.
             // Example cURL: curl -X GET http://your-domain.com/Class_purchases.php/uid/some_user_id
             echo json_encode($purchases->getPurchasesByUid($segments[1]));
        } else {
            // Respond with Bad Request for invalid GET requests.
            http_response_code(400);
            echo json_encode(['error' => 'Invalid GET request for purchases']);
        }
    } elseif ($method === 'POST') {
        // Decode the JSON request body.
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($segments[0]) && $segments[0] === 'purchase') {
            // Handle POST request to /purchase to create a new purchase.
            // Example cURL: curl -X POST -H "Content-Type: application/json" -d '{"product_id":"123","uid":"abc","date":1678886400,"session":"xyz"}' http://your-domain.com/Class_purchases.php/purchase
            echo json_encode($purchases->createPurchase($data));
        } else {
            // Respond with Bad Request for invalid POST requests.
            http_response_code(400);
            echo json_encode(['error' => 'Invalid POST request for purchases']);
        }
    } elseif (in_array($method, ['PUT', 'PATCH'])) {
        if (isset($segments[0]) && $segments[0] === 'purchase' && isset($segments[1])) {
            // Decode the JSON request body.
            $data = json_decode(file_get_contents('php://input'), true);
            // Handle PUT/PATCH request to /purchase/{id} to update a purchase.
            // Example cURL (PUT):???curl -X PUT???-H "Content-Type: application/json" -d '{"quantity":3}' http://your-domain.com/Class_purchases.php/purchase/some_purchase_id
            // Example cURL (PATCH): curl -X PATCH -H "Content-Type: application/json" -d '{"quantity":3}' http://your-domain.com/Class_purchases.php/purchase/some_purchase_id
            echo json_encode($purchases->updatePurchase($segments[1], $data));
        } else {
            // Respond with Bad Request for invalid PUT/PATCH requests.
            http_response_code(400);
            echo json_encode(['error' => 'Invalid PUT/PATCH request for purchases']);
        }
    } elseif ($method === 'DELETE') {
        if (isset($segments[0]) && $segments[0] === 'purchase' && isset($segments[1])) {
            // Handle DELETE request to /purchase/{id} to delete a purchase.
            // Example cURL: curl -X DELETE http://your-domain.com/Class_purchases.php/purchase/some_purchase_id
            echo json_encode($purchases->deletePurchase($segments[1]));
        } else {
            // Respond with Bad Request for invalid DELETE requests.
            http_response_code(400);
            echo json_encode(['error' => 'Invalid DELETE request for purchases']);
        }
    } else {
        // Respond with Method Not Allowed for other HTTP methods.
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed for purchases']);
    }
}
?>