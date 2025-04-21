<?php
require_once 'firestore.php';
class Purchases {
    private const COLLECTION_NAME = 'tb_purchases';
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
            $jsonData = json_encode(['fields' => $this->_prepareFields($data)]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            error_log("Firestore API cURL Error for Purchases: " . $curlError);
            return ['error' => 'cURL Error', 'details' => $curlError];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Firestore API HTTP Error {$httpCode} for Purchases: " . $response);
            $decodedResponse = json_decode($response, true);
            return $decodedResponse ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response];
        }

        return json_decode($response, true);
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

    // Create a new purchase record.
    public function createPurchase(array $data): ?array {
        return $this->_makeRequest('', 'POST', $data);
    }

    // Get a specific purchase record by its document ID.
    public function getPurchase(string $purchaseId): ?array {
        return $this->_makeRequest($purchaseId, 'GET');
    }

    // List all purchase records.
    public function listPurchases(): ?array {
        $response = $this->_makeRequest('', 'GET');
        return $response; // Might need parsing.
    }

    // Update an existing purchase record.
    public function updatePurchase(string $purchaseId, array $data): ?array {
        return $this->_makeRequest($purchaseId, 'PATCH', $data);
    }

    // Delete a purchase record.
    public function deletePurchase(string $purchaseId): ?array {
        return $this->_makeRequest($purchaseId, 'DELETE');
    }

    // Query purchases based on 'product_id' and 'uid'.
    public function getPurchasesByProductAndUid(string $productId, string $uid): ?array {
        $url = FIRESTORE_API_BASE_URL . ':runQuery?key=' . $this->apiKey;
        $ch = curl_init($url);

        $queryData = [
            'structuredQuery' => [
                'from' => [['collectionId' => self::COLLECTION_NAME]],
                'where' => [
                    'compositeFilter' => [
                        'op' => 'AND',
                        'filters' => [
                            [
                                'fieldFilter' => [
                                    'field' => ['fieldPath' => 'product_id'],
                                    'op' => 'EQUAL',
                                    'value' => ['stringValue' => $productId],
                                ],
                            ],
                            [
                                'fieldFilter' => [
                                    'field' => ['fieldPath' => 'uid'],
                                    'op' => 'EQUAL',
                                    'value' => ['stringValue' => $uid],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $jsonData = json_encode($queryData);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            error_log("Firestore API cURL Error for getPurchasesByProductAndUid: " . $curlError);
            return ['error' => 'cURL Error', 'details' => $curlError];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Firestore API HTTP Error {$httpCode} for getPurchasesByProductAndUid: " . $response);
            $decodedResponse = json_decode($response, true);
            return $decodedResponse ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response];
        }

        $decodedResponse = json_decode($response, true);
        if (isset($decodedResponse[0]['document'])) {
            // Assuming you expect one or more results, parse each document
            $results = [];
            foreach ($decodedResponse as $item) {
                if (isset($item['document'])) {
                    $results[] = $this->_parseFirestoreDocument($item['document']);
                }
            }
            return $results;
        }
        return []; // Return an empty array if no matching purchases are found
    }

    private function _parseFirestoreDocument(array $document): array {
        $output = [];
        $nameParts = explode('/', $document['name']);
        $output['id'] = end($nameParts); // Document ID
        if (isset($document['fields'])) {
            foreach ($document['fields'] as $key => $valueWrapper) {
                $type = key($valueWrapper);
                $value = $valueWrapper[$type];
                $output[$key] = match ($type) {
                    'stringValue' => $value,
                    'booleanValue' => (bool)$value,
                    'integerValue' => (int)$value,
                    default => $value,
                };
            }
        }
        return $output;
    }
}
?>