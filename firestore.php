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
// Make all PHP date/time functions default to Asia/Hong_Kong (UTC+8)
date_default_timezone_set('Asia/Hong_Kong');

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
                     $dateTimeValue = (new DateTime("@$timestampInSeconds"))->setTimezone(new DateTimeZone('Asia/Hong_Kong')); // Default to Hong Kong time
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
    private function _prepareFieldValue($value): array {
        if (is_string($value))   return ['stringValue'  => $value]; // String.
        if (is_bool($value))    return ['booleanValue' => $value]; // Boolean.
        if (is_null($value))     return ['nullValue'    => null]; // Null.
        if (is_int($value))      return ['integerValue' => $value]; // Integer.
        if (is_float($value))    return ['doubleValue'  => $value]; // Float (double).
        // Handle DateTime/DateTimeImmutable for Timestamp value.
        if ($value instanceof DateTimeInterface) {
            try {
                $currentTz = $value->getTimezone();
                $hkTz = new DateTimeZone('Asia/Hong_Kong');
                $utcTz = new DateTimeZone('UTC');
                // If time is in UTC, reinterpret it as if it were in HK (failsafe default).
                if ($currentTz->getName() === 'UTC') {
                    // Rebuild DateTime as if it's HK-local time.
                    $value = new DateTime($value->format('Y-m-d H:i:s'), $hkTz);
                }
                // Convert to UTC for Firestore.
                $value->setTimezone($utcTz);
                // Format in RFC3339 with microseconds and Z suffix
                $rfc3339String = $value->format('Y-m-d\TH:i:s.v\Z');
                return ['timestampValue' => $rfc3339String];
            } catch (Exception $e) {
                error_log("FirestoreApiTrait: Error formatting DateTime for timestampValue: " . $e->getMessage());
                return ['stringValue' => $value->format(DateTime::ATOM)];
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

        // If Firestore returned createTime, parse in UTC then convert to UTC+8 and format with microseconds
        if (isset($document['createTime'])) {
            // Parse the ISO8601 timestamp as UTC
            $dtUtc = new DateTimeImmutable($document['createTime'], new DateTimeZone('UTC'));
            // Convert to Asia/Hong_Kong (UTC+8)
            $dtLocal = $dtUtc->setTimezone(new DateTimeZone('Asia/Hong_Kong'));
            // Store formatted "d-m-Y H:i:s.u" string
            $output['createTime'] = $dtLocal->format('d-m-Y H:i:s.u');
        }
        // If Firestore returned updateTime, parse in UTC then convert to UTC+8 and format with microseconds
        if (isset($document['updateTime'])) {
            // Parse the ISO8601 timestamp as UTC
            $dtUtc = new DateTimeImmutable($document['updateTime'], new DateTimeZone('UTC'));
            // Convert to Asia/Hong_Kong (UTC+8)
            $dtLocal = $dtUtc->setTimezone(new DateTimeZone('Asia/Hong_Kong'));
            // Store formatted "d-m-Y H:i:s.u" string
            $output['updateTime'] = $dtLocal->format('d-m-Y H:i:s.u');
        }

        // If Firestore 'date' field wrapper turned into a DateTimeInterface, flatten it the same way
        if (isset($output['date']) && $output['date'] instanceof DateTimeInterface) {
            // Re-format the DateTimeInterface to "d-m-Y H:i:s.u"
            $output['date'] = $output['date']->setTimezone(new DateTimeZone('Asia/Hong_Kong'))->format('d-m-Y H:i:s.u');
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
        $dt = null;

        // If already a DateTimeInterface, use it
        if ($dateValue instanceof DateTimeInterface) {
            $dt = $dateValue;
        }
        // Else if a non??empty string, try to parse it as ISO8601
        elseif (is_string($dateValue) && $dateValue !== '') {
            try {
                $dt = new DateTimeImmutable($dateValue, new DateTimeZone('UTC'));
                // Convert parsed UTC to Asia/Hong_Kong
                $dt = $dt->setTimezone(new DateTimeZone('Asia/Hong_Kong'));
            } catch (Exception $e) {
                error_log("formatFirestoreDate: parse error for '$dateValue' ?  " . $e->getMessage());
                return '';
            }
        }
        // Else if an array: map each element recursively
        elseif (is_array($dateValue) && !empty($dateValue)) {
            $out = [];
            foreach ($dateValue as $item) {
                $formatted = formatFirestoreDate($item, $contextOrFormat);
                if ($formatted !== '') {
                    $out[] = $formatted;
                }
            }
            return implode(', ', $out);
        } else {
            // Unsupported type
            error_log("formatFirestoreDate: unsupported type " . gettype($dateValue));
            return '';
        }

        // Decide which format string to use
        $formatString = match (strtolower($contextOrFormat)) {
            'table' => 'd-m-Y H:i:s',  // human??readable table
            'chart' => 'Y-m-d H:i',    // e.g. for chart axes
            default => $contextOrFormat !== '' ? $contextOrFormat : DateTimeInterface::ATOM,
        };

        // Return the formatted result
        try {
            return $dt->format($formatString);
        } catch (Exception $e) {
            error_log("formatFirestoreDate: formatting error with '$formatString' ?  " . $e->getMessage());
            return '';
        }
    }
}