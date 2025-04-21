<?php
// Define Firebase project ID.
define('FIREBASE_PROJECT_ID', 'web-assignment-4237d');
// Define Firestore database ID.
define('FIREBASE_DATABASE_ID', 'web-development');
// Define API key for REST calls.
define('FIREBASE_API_KEY', 'AIzaSyCQDJLKGSEzBn3HMqe7c3KHp1iUapZOYm4');
// Base URL for Firestore document operations.
define('FIRESTORE_API_BASE_URL', 'https://firestore.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/databases/' . FIREBASE_DATABASE_ID . '/documents');
// Define internal API base URL
define('INTERNAL_API_BASE_URL', 'http://localhost/Web%20Assignment');

// Import necessary PHP classes for date and time.
//use DateTime;
//use DateTimeZone;
//use DateTimeImmutable; // For parsing received timestamps.
//use DateTimeInterface; // Interface for DateTime objects.

// Helper function to set common cURL options for Firestore requests.
if (!function_exists('setFirestoreCurlOptions')) {
    function setFirestoreCurlOptions($ch, string $method): void {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as string.
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ // Set standard headers.
            'Content-Type: application/json', // Request body is JSON.
            'Accept: application/json' // Expect JSON response.
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); // Set HTTP method.
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set timeout to 10 seconds.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL certificate.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // Verify host against certificate.
    }
}

// Trait for common Firestore REST API operations.
trait FirestoreApiTrait {
    // Base URL for the specific collection.
    protected string $baseUrl;
    // API key for authentication.
    protected string $apiKey;

    // Trait constructor to initialize base URL and API key.
    public function __construct() {
        $this->baseUrl = FIRESTORE_API_BASE_URL . '/' . $this->getCollectionName(); // Build collection URL.
        $this->apiKey  = FIREBASE_API_KEY; // Set API key.
    }

    // Trait initialization method. Classes using this trait must call this method in their constructor.
    protected function __firestoreApiTraitInit(): void {
        $this->baseUrl = FIRESTORE_API_BASE_URL . '/' . $this->getCollectionName(); // Build collection URL.
        $this->apiKey  = FIREBASE_API_KEY; // Set API key.
    }

    // Make REST API request to Firestore (GET, POST, PATCH, DELETE).
    protected function _makeRequest(string $documentId = '', string $method = 'GET', ?array $data = null): ?array {
        $preparedFieldsData = null; // Prepared data for write operations.
        if ($data !== null && ($method === 'POST' || $method === 'PATCH')) {
            $preparedFieldsData = $this->_prepareFields($data); // Prepare raw data.
        } elseif ($data !== null) {
             error_log("FirestoreApiTrait: Data provided for {$method} request, ignoring for {$this->getCollectionName()}/{$documentId}."); // Log unexpected data.
        }

        $url = $this->baseUrl; // Build full request URL.
        if ($documentId) $url .= '/' . urlencode($documentId); // Append document ID/endpoint.
        $url .= '?key=' . $this->apiKey;

        $ch = curl_init($url); // Initialize cURL.
        setFirestoreCurlOptions($ch, $method); // Apply common options.

        if ($preparedFieldsData !== null) { // Attach prepared data for write operations.
            $jsonData = json_encode(['fields' => $preparedFieldsData]); // Encode prepared fields.
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Set payload.
            error_log("Firestore API Payload for {$this->getCollectionName()}: " . $jsonData); // Log payload.
        }

        $response = curl_exec($ch); // Execute request.
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code.
        $curlError = curl_error($ch); // Check for cURL errors.
        curl_close($ch); // Close cURL.

        if ($curlError) { // Handle cURL errors.
            error_log("Firestore API cURL Error for {$method} {$url}: " . $curlError); // Log error.
            return ['error' => 'cURL Error', 'details' => $curlError]; // Return error structure.
        }

        $decodedResponse = json_decode($response, true); // Decode response body.

        if ($httpCode < 200 || $httpCode >= 300) { // Handle HTTP errors (non-2XX status).
            error_log("Firestore API HTTP Error {$httpCode} for {$method} {$url}: " . $response); // Log error.
             return $decodedResponse ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response]; // Return error details.
        }

        return $decodedResponse; // Return decoded successful response.
    }

    // Convert PHP array to Firestore 'fields' format for writing.
    // Handles conversion of specific types like 'date' (Unix timestamp) to Timestamp.
    private function _prepareFields(array $data): array {
        $fields = []; // Prepared fields array.
        foreach ($data as $key => $value) { // Iterate through input data.
            // Handle 'date' field if it's an integer (Unix timestamp).
            if ($key === 'date' && is_int($value)) {
                 try {
                     $timestampInSeconds = $value / 1000.0; // Converts the integer value from milliseconds to seconds (float)
                     $dateTimeValue = (new DateTime("@$timestampInSeconds"))->setTimezone(new DateTimeZone('UTC'));
                     //$dateTimeValue = (new DateTime("@$value"))->setTimezone(new DateTimeZone('UTC')); // Convert Unix timestamp to DateTime (UTC).
                     $fields[$key] = $this->_prepareFieldValue($dateTimeValue); // Pass DateTime to _prepareFieldValue.
                 } catch (Exception $e) {
                     error_log("FirestoreApiTrait: Invalid timestamp integer for field '" . $key . "': " . $value . " - " . $e->getMessage()); // Log error.
                      $fields[$key] = $this->_prepareFieldValue($value); // Fallback to default integer handling.
                 }
            } else { // For other types, use generic preparation.
                 $fields[$key] = $this->_prepareFieldValue($value);
            }
        }
        return $fields; // Return prepared fields.
    }

    // Wrap PHP values in Firestore type envelopes for writing.
    // Handles standard PHP types and DateTime/DateTimeImmutable objects.
    private function _prepareFieldValue($value): array {
         if (is_string($value))   return ['stringValue'  => $value]; // String.
         if (is_bool($value))    return ['booleanValue' => $value]; // Boolean.
         if (is_null($value))     return ['nullValue'    => null]; // Null.
         if (is_int($value))      return ['integerValue' => $value]; // Integer.
         if (is_float($value))    return ['doubleValue'  => $value]; // Float (double).
         // Handle DateTime/DateTimeImmutable for Timestamp value.
         if ($value instanceof DateTimeInterface) {
              try {
                  $rfc3339String = $value->format('Y-m-d\TH:i:s.v\Z'); // Format to RFC 3339 (Y-m-d).
                  return ['timestampValue' => $rfc3339String]; // Timestamp.
              } catch (Exception $e) {
                   error_log("FirestoreApiTrait: Error formatting DateTime for timestampValue: " . $e->getMessage()); // Log error.
                   return ['stringValue' => $value->format(DateTime::ATOM)]; // Fallback to string.
              }
         }
         // Handle objects (excluding DateTime) as mapValue recursively.
         if (is_object($value)) {
              if (!($value instanceof DateTimeInterface)) { // If NOT a DateTime object.
                return ['mapValue' => ['fields' => $this->_prepareFields((array)$value)]]; // Map.
              }
         }
         // Handle arrays as arrayValue recursively.
         if (is_array($value)) {
            $arrayValues = [];
            foreach ($value as $item) {
                $arrayValues[] = $this->_prepareFieldValue($item); // Recurse for array items.
            }
            return ['arrayValue' => ['values' => $arrayValues]]; // Array.
         }
         error_log("FirestoreApiTrait: Unsupported value type encountered: " . gettype($value)); // Log error.
         return ['nullValue' => null]; // Default to null.
    }

    // Parse Firestore document JSON to PHP array for reading.
    protected function _parseFirestoreDocument(array $document): array {
        $output = []; // Parsed document data.
        $nameParts = explode('/', $document['name']); // Split resource name.
        $output['ID'] = end($nameParts); // Extract Document ID.

        if (isset($document['fields'])) { // Unwrap fields.
            foreach ($document['fields'] as $key => $valueWrapper) {
                $output[$key] = $this->_parseFirestoreValueWrapper($valueWrapper); // Parse each field.
            }
        }

        // Flatten 'date' field if it was parsed as a DateTime object
        if (isset($output['date']) && $output['date'] instanceof DateTimeInterface) {
            $output['date'] = $output['date']->format('d-m-Y H:i:s.u'); // Format to microsecond string
        }

        // Format Firestore timestamps as plain strings
        if (isset($document['createTime'])) {
            try {
                $dt = new DateTimeImmutable($document['createTime']); // Parse ISO timestamp
                $output['createTime'] = $dt->format('d-m-Y H:i:s.u');  // Format with microseconds
            } catch (Exception $e) {
                $output['createTime'] = $document['createTime']; // Fallback
            }
        }
        if (isset($document['updateTime'])) {
            try {
                $dt = new DateTimeImmutable($document['updateTime']);
                $output['updateTime'] = $dt->format('d-m-Y H:i:s.u');
            } catch (Exception $e) {
                $output['updateTime'] = $document['updateTime'];
            }
        }
        return $output; // Return parsed document.
    }

    // Convert one Firestore value wrapper to PHP value for reading.
    private function _parseFirestoreValueWrapper(array $valueWrapper): mixed {
        if (empty($valueWrapper)) return null; // Handle empty wrapper.
        $type  = key($valueWrapper); // Get Firestore type.
        $value = $valueWrapper[$type] ?? null; // Get value.

        // Map Firestore types to PHP values.
        return match ($type) {
            'referenceValue'=> $value, // Reference string.
            'stringValue'   => $value, // String.
            'booleanValue'  => (bool)$value, // Boolean.
            'integerValue'  => (int)$value, // Integer.
            'doubleValue'   => (float)$value, // Float.
            'nullValue'     => null, // Null.
            // Handle timestamp: Parse RFC 3339 to DateTimeImmutable.
            'timestampValue'=> (function() use ($value) {
                try {
                    $dt = new DateTimeImmutable($value, new DateTimeZone('UTC'));
                    // Convert to UTC+8 (e.g., Hong Kong timezone) for consistency
                    return $dt->setTimezone(new DateTimeZone('Asia/Hong_Kong'));
                    } catch (Exception $e) {
                        error_log("FirestoreApiTrait: Error parsing timestamp: " . $e->getMessage());
                        return $value;
                    }
                })(),
            // Handle GeoPoint: Return lat/lng array.
            'geoPointValue' => ['latitude' => $value['latitude'] ?? null, 'longitude' => $value['longitude'] ?? null],
            // Handle array: Recurse for items.
            'arrayValue'    => array_map(fn($v) => $this->_parseFirestoreValueWrapper($v), $value['values'] ?? []),
            // Handle map: Recurse for fields.
            'mapValue'      => $this->_parseFirestoreMap($value['fields'] ?? []),
            // Default for unhandled types.
            default         => (function() use ($type, $value) { error_log("FirestoreApiTrait: Unhandled Firestore value type '" . $type . "' encountered."); return $value; })(),
        };
    }
    // Convert Firestore mapValue fields to PHP associative array. Used recursively.
    private function _parseFirestoreMap(array $fields): array {
        $map = []; // Map array.
        foreach ($fields as $key => $valueWrapper) { // Iterate through fields.
            $map[$key] = $this->_parseFirestoreValueWrapper($valueWrapper); // Recurse for each field.
        }
        return $map; // Return map array.
    }
    // Abstract method: returns the Firestore collection name. Must be implemented by using class.
    abstract protected function getCollectionName(): string;
}

// Reusable basic helper function to perform internal GET requests to own PHP REST APIs.
// This function is useful for centralising internal API access for dashboard/chart use.
// Error logs are added for easier debugging.
// @param string $endpoint The API path relative to INTERNAL_API_BASE_URL (e.g., '/Class_products.php/all').
// @return array|null Decoded response array on success, or an error structure array on failure.
if (!function_exists('callInternalApiGet')) {
    function callInternalApiGet(string $endpoint): ?array {
        $url = INTERNAL_API_BASE_URL . $endpoint; // Construct full API URL based on project base
        $ch = curl_init($url); // Initialise cURL session
   
        setFirestoreCurlOptions($ch, 'GET'); // Apply shared cURL options with GET method
        // Note: setFirestoreCurlOptions already sets Content-Type: application/json and Accept: application/json

        $response = curl_exec($ch); // Execute HTTP request
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status
        $curlError = curl_error($ch); // Capture cURL error if any
        curl_close($ch); // Close cURL session

        if ($curlError) { // Log and return on cURL-level error
            error_log("callInternalApiGet cURL Error for {$endpoint}: " . $curlError);
            return ['error' => 'cURL Error', 'details' => $curlError, 'url' => $url];
        }
        $decoded = json_decode($response, true); // Try decoding the response
        $jsonError = json_last_error(); // Get the last JSON error code
        // Check for JSON decoding errors OR if the response was not a string (could indicate a fundamental issue)
        if (!is_string($response) || $jsonError !== JSON_ERROR_NONE) {
            // Log the JSON decode error details, including the raw response if available.
            error_log("callInternalApiGet JSON Decode Error for {$endpoint}: " . json_last_error_msg() . ". Response: " . (is_string($response) ? $response : print_r($response, true)));
            return ['error' => 'JSON Decode Error', 'details' => json_last_error_msg(), 'url' => $url, 'response' => $response];
        }

        if ($httpCode < 200 || $httpCode >= 300) { // Handle non-2XX responses (API-level errors)
            // Log the HTTP error and return a standard error structure.
            // Include the decoded JSON details if available, otherwise the raw response.
            error_log("callInternalApiGet HTTP Error {$httpCode} for {$endpoint}: " . $response);
            return ['error' => "HTTP Error {$httpCode}", 'details' => $decoded ?: $response, 'url' => $url]; // Use decoded details if available, else raw response
        }
        return $decoded; // Return decoded data array if successful (HTTP status 2XX and valid JSON)
    }
}

// Reusable basic helper function to perform internal POST requests to own PHP REST APIs.
// This function is useful for centralising internal API access for dashboard/chart use.
// Error logs are added for easier debugging.
// @param string $endpoint The API path relative to INTERNAL_API_BASE_URL (e.g., '/Class_products.php/product').
// @param array $data The PHP array payload to send as JSON in the request body.
// @return array|null Decoded response array on success, or an error structure array on failure.
if (!function_exists('callInternalApiPost')) {
    function callInternalApiPost(string $endpoint, array $data): ?array {
        $url = INTERNAL_API_BASE_URL . $endpoint; // Construct full API URL
        $ch = curl_init($url); // Initialise cURL session

        setFirestoreCurlOptions($ch, 'POST'); // Apply shared cURL options with POST method
        // Note: setFirestoreCurlOptions sets Content-Type: application/json and Accept: application/json

        $jsonData = json_encode($data); // Encode the PHP data array into a JSON string for the payload
        if ($jsonData === false) { // Check if json_encode failed
            error_log("callInternalApiPost JSON Encode Error for {$endpoint}: " . json_last_error_msg());
            return ['error' => 'JSON Encode Error', 'details' => json_last_error_msg(), 'url' => $url, 'payload' => $data];
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Set the JSON payload for the POST request

        $response = curl_exec($ch); // Execute HTTP request
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status
        $curlError = curl_error($ch); // Capture cURL error if any
        curl_close($ch); // Close cURL session

        if ($curlError) { // Log and return on cURL-level error
            error_log("callInternalApiPost cURL Error for {$endpoint}: " . $curlError);
            return ['error' => 'cURL Error', 'details' => $curlError, 'url' => $url, 'payload' => $data];
        }

        $decoded = json_decode($response, true); // Try decoding the response
        $jsonError = json_last_error(); // Get the last JSON error code

        // Check for JSON decoding errors OR if the response was not a string (could indicate a fundamental issue)
        if (!is_string($response) || $jsonError !== JSON_ERROR_NONE) {
            // Log the JSON decode error details, including the raw response if available.
            error_log("callInternalApiPost JSON Decode Error for {$endpoint}: " . json_last_error_msg() . ". Response: " . (is_string($response) ? $response : print_r($response, true)));
            return ['error' => 'JSON Decode Error', 'details' => json_last_error_msg(), 'url' => $url, 'response' => $response, 'payload' => $data];
        }

        if ($httpCode < 200 || $httpCode >= 300) { // Handle non-2XX responses (API-level errors)
            // Log the HTTP error and return a standard error structure.
            // Include the decoded JSON details if available, otherwise the raw response.
            error_log("callInternalApiPost HTTP Error {$httpCode} for {$endpoint}: " . $response);
            return ['error' => "HTTP Error {$httpCode}", 'details' => $decoded ?: $response, 'url' => $url, 'payload' => $data]; // Use decoded details if available, else raw response
        }
        return $decoded; // Return decoded data array if successful (HTTP status 2XX and valid JSON)
    }
}

// Reusable basic helper function to perform internal PATCH requests to own PHP REST APIs.
// This function is useful for centralising internal API access for dashboard/chart use.
// Error logs are added for easier debugging.
// @param string $endpoint The API path relative to INTERNAL_API_BASE_URL (e.g., '/Class_products.php/product/{id}').
// @param array $data The PHP array payload to send as JSON in the request body.
// @return array|null Decoded response array on success, or an error structure array on failure.
if (!function_exists('callInternalApiPatch')) {
    function callInternalApiPatch(string $endpoint, array $data): ?array {
        $url = INTERNAL_API_BASE_URL . $endpoint; // Construct full API URL
        $ch = curl_init($url); // Initialise cURL session

        setFirestoreCurlOptions($ch, 'PATCH'); // Apply shared cURL options with PATCH method
        // Note: setFirestoreCurlOptions sets Content-Type: application/json and Accept: application/json

        $jsonData = json_encode($data); // Encode the PHP data array into a JSON string for the payload
            if ($jsonData === false) { // Check if json_encode failed
            error_log("callInternalApiPatch JSON Encode Error for {$endpoint}: " . json_last_error_msg());
            return ['error' => 'JSON Encode Error', 'details' => json_last_error_msg(), 'url' => $url, 'payload' => $data];
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Set the JSON payload for the PATCH request

        $response = curl_exec($ch); // Execute HTTP request
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status
        $curlError = curl_error($ch); // Capture cURL error if any
        curl_close($ch); // Close cURL session

        if ($curlError) { // Log and return on cURL-level error
            error_log("callInternalApiPatch cURL Error for {$endpoint}: " . $curlError);
            return ['error' => 'cURL Error', 'details' => $curlError, 'url' => $url, 'payload' => $data];
        }
        $decoded = json_decode($response, true); // Try decoding the response
        $jsonError = json_last_error(); // Get the last JSON error code
        // Check for JSON decoding errors OR if the response was not a string (could indicate a fundamental issue)
        if (!is_string($response) || $jsonError !== JSON_ERROR_NONE) {
            // Log the JSON decode error details, including the raw response if available.
            error_log("callInternalApiPatch JSON Decode Error for {$endpoint}: " . json_last_error_msg() . ". Response: " . (is_string($response) ? $response : print_r($response, true)));
            return ['error' => 'JSON Decode Error', 'details' => json_last_error_msg(), 'url' => $url, 'response' => $response, 'payload' => $data];
        }
        if ($httpCode < 200 || $httpCode >= 300) { // Handle non-2XX responses (API-level errors)
            // Log the HTTP error and return a standard error structure.
            // Include the decoded JSON details if available, otherwise the raw response.
            error_log("callInternalApiPatch HTTP Error {$httpCode} for {$endpoint}: " . $response);
            return ['error' => "HTTP Error {$httpCode}", 'details' => $decoded ?: $response, 'url' => $url, 'payload' => $data]; // Use decoded details if available, else raw response
        }
        return $decoded; // Return decoded data array if successful (HTTP status 2XX and valid JSON)
    }
}

// Utility function: Format a Firestore DateTime object differently based on use case
if (!function_exists('formatFirestoreDate')) {
    // @param mixed $dateValue The date value (expected to be DateTimeInterface or string).
    // @param string $contextOrFormat Either 'chart'(X), 'table', or a custom date format string (e.g., 'Y-m-d').
    // @return string A formatted date string, or an empty string on failure.
    function formatFirestoreDate(mixed $dateValue, string $contextOrFormat = ''): string {
    $dateTimeObj = null;
    // Try to get a DateTimeInterface object
    if ($dateValue instanceof DateTimeInterface) {
        $dateTimeObj = $dateValue;
    } elseif (is_string($dateValue) && $dateValue !== '') {
        // If it's a non-empty string, attempt to parse it into a DateTimeImmutable object
        try {
            // Assuming the string is in a format DateTimeImmutable constructor can understand (like RFC 3339)
            $dateTimeObj = new DateTimeImmutable($dateValue);
        } catch (Exception $e) {
            // Log parsing errors but don't fail hard
            error_log("formatFirestoreDate: Error parsing date string '" . $dateValue . "': " . $e->getMessage());
            return ''; // Return empty string on parse failure
        }
    } else {
        // Input is not a DateTime object or a string
        // Check if it's an array containing items to format (as in the user's original function)
        if (is_array($dateValue) && !empty($dateValue)) {
            // Handle array case: Format each item and join
            $formattedArray = [];
            foreach ($dateValue as $item) {
                // Recursively call formatFirestoreDate for each item in the array
                // Pass the same context/format. This handles arrays of strings or DateTime objects.
                $formattedArray[] = formatFirestoreDate($item, $contextOrFormat);
            }
            return implode(', ', array_filter($formattedArray, fn($v) => $v !== '')); // Join non-empty formatted strings
        } else {
            // It's truly an invalid type
        error_log("formatFirestoreDate: Invalid date value type provided: " . gettype($dateValue));
        return ''; // Return empty string for invalid types
        }
    }

    // Format DateTime object based on context or format string
    if ($dateTimeObj instanceof DateTimeInterface) {
        try {
            // Determine the format string based on context
            $formatString = match ($contextOrFormat) {
                'table' => 'd-m-Y H:i:s', // Example: "20-04-2025 14:33:01"
                default => 'Y-m-d',
            };
            // If the format string is empty, use a default RFC 3339 format (like ATOM)
            if ($formatString === '') {
                $formatString = DateTimeInterface::ATOM; // Example: 2005-08-15T15:52:01+00:00
            }
            return $dateTimeObj->format($formatString);
        } catch (Exception $e) {
            // Log formatting errors
            error_log("formatFirestoreDate: Error formatting date with format '" . $formatString . "': " . $e->getMessage());
            return 'Format failure'; // Return empty string on format failure
        }
    }
    return ''; // Should not reach here if $dateTimeObj was successfully created/identified and formatted
    }
}