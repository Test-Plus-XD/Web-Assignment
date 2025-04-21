<!DOCTYPE html>
<html>
<?php
// Set page variables for head.php
$pageTitle = 'Library';
$pageCSS = 'library.css';
// Include the head section (contains session_start, global JS vars, etc.)
require_once 'head.php';
// Define base URL for internal API calls
define('INTERNAL_API_BASE_URL', 'http://localhost/Web%20Assignment');

// Handles cURL execution, error checking, and JSON decoding.
function callInternalApiGet(string $endpoint): ?array {
    // Build the full URL for the API endpoint.
    $url = INTERNAL_API_BASE_URL . $endpoint;
    // Initialize cURL session.
    $ch = curl_init($url);
    // Set cURL options for a GET request.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as string.
    curl_setopt($ch, CURLOPT_HTTPGET, true); // Explicitly set as GET method.
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']); // Request JSON response.
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if (strpos($url, 'localhost') !== false) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }
    // Execute cURL request.
    $response = curl_exec($ch);
    // Show exact cURL error
    if (curl_errno($ch)) echo 'cURL error: ' . curl_error($ch);
    // Get HTTP status code.
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // Check for cURL errors.
    $curlError = curl_error($ch);
    // Close cURL session.
    curl_close($ch);
    // Handle cURL errors.
    if ($curlError) {
        error_log("Library API Call Error (cURL) for {$url}: " . $curlError);
        return ['error' => 'cURL Error', 'details' => $curlError];
    }
    // Handle HTTP errors (non-2xx status codes).
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("Library API Call Error (HTTP {$httpCode}) for {$url}: " . $response);
        $decoded = json_decode($response, true);
        return $decoded ?: ['error' => "HTTP Error {$httpCode}", 'details' => $response];
    }
    // Decode the JSON response.
    $decoded = json_decode($response, true);
    // Check for JSON decoding errors.
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Library API Call Error (JSON Decode) for {$url}: " . json_last_error_msg() . " Response: " . $response);
        return ['error' => 'JSON Decode Error', 'details' => json_last_error_msg(), 'response' => $response];
    }
    return $decoded; // Return decoded data.
}

// Check if the user is logged in and retrieve their user_id from the session
$user_id = $_SESSION['user_id'] ?? null; // Use null coalescing for safety

// Fetch Purchase Data by calling the API Endpoint
// Construct the endpoint URL for getting purchases by user ID.
$purchasesApiEndpoint = "/Class_purchases.php/uid/" . urlencode($user_id);
// Call the internal API helper function.
$purchaseRecords = callInternalApiGet($purchasesApiEndpoint);

// Initialize array to hold combined product and purchase data.
$libraryItems = [];
$fetchError = false; // Flag to check for errors during data fetching.

// Check if fetching purchase records resulted in an error or no records.
// The API should return an array of documents or an error structure.
if ($purchaseRecords !== null && !isset($purchaseRecords['error'])) { // Check for successful response (not null and no 'error' key)
    if (!empty($purchaseRecords)) {
        // Fetch Product Details for each purchased item by calling the API Endpoint
        // Iterate through each purchase record fetched for the user.
        foreach ($purchaseRecords as $purchase) {
            // Ensure the purchase record has a product_id key from the API response.
            $productId = $purchase['product_id'] ?? null;

            if ($productId) {
                // Construct the endpoint URL for getting product details by product ID.
                $productApiEndpoint = "/Class_products.php/product/" . urlencode($productId);
                // Call the internal API helper function.
                $productDetails = callInternalApiGet($productApiEndpoint);

                // Check if fetching product details was successful (not null and no 'error' key).
                // The API for a single product should return a single document array or an error.
                if ($productDetails !== null && !isset($productDetails['error'])) {
                    // The 'date' field comes as a string from the JSON API response (RFC 3339 format).
                    // Convert it back to a DateTimeImmutable object for consistent handling in the display loop.
                    if (isset($purchase['date']) && is_string($purchase['date'])) {
                        try {
                            $purchase['date'] = new DateTimeImmutable($purchase['date']);
                        } catch (Exception $e) {
                            error_log("Library: Error parsing date string from purchase API for purchase ID: " . ($purchase['ID'] ?? 'Unknown ID') . " - " . $purchase['date'] . " - " . $e->getMessage());
                        }
                    }
                    // Combine product details and purchase data.
                    // array_merge puts values from the second array on top in case of key conflicts.
                    $combinedData = array_merge($productDetails, $purchase);
                    // Add the combined data to the library items list.
                    $libraryItems[] = $combinedData;
                } else {
                    // Log or handle the case where product details couldn't be fetched for a purchase.
                    error_log("Library: Failed to fetch product details for product ID: " . $productId . " from purchase record: " . ($purchase['ID'] ?? 'Unknown ID') . ". Response: " . json_encode($productDetails));
                    // You could add a placeholder item indicating an error or skip this item. Skipping for now.
                }
            } else {
                // Log or handle purchase records missing a product_id.
                 error_log("Library: Purchase record missing product_id. Purchase ID: " . ($purchase['ID'] ?? 'Unknown ID') . ". Record: " . json_encode($purchase));
            }
        }
    }
} else {
    // Handle error if fetching purchase records failed.
    $fetchError = true;
    error_log("Library: Failed to fetch purchase records for user " . $user_id . ". Response: " . json_encode($purchaseRecords));
}
?>
<body style="font-family:Zen Maru Gothic">
    <main class="library_main">
        <div class="container">
            <h1>Your Owned Products</h1>
            <div class="row d-flex justify-content-evenly">
                <?php if ($fetchError): ?>
                    <div class="col-12 text-center">
                        <p class="text-danger">Error loading your library. Please try again.</p>
                    </div>
                <?php elseif (empty($libraryItems)): ?>
                    <div class="col-12 text-center">
                        <p class="text-warning" style="font-size: 1.91vw; background-color: #2500caca;">No products owned yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($libraryItems as $item): ?>
                        <div class="p-3 col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card">
                                <img src="<?php echo htmlspecialchars($item['imageSrc'] ?? ''); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['imageAlt'] ?? 'Product Image'); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['cardTitle'] ?? 'Untitled Product'); ?></h5>
                                    <h5 class="card-title">
                                        <?php
                                        // Check if 'date' key exists and is a DateTimeInterface object (should be DateTimeImmutable after parsing)
                                        if (isset($item['date']) && $item['date'] instanceof DateTimeInterface) {
                                            echo 'Purchased Date: <br>' . htmlspecialchars($item['date']->format('Y-m-d H:i:s')); // Format the DateTime object
                                        } elseif (isset($item['date'])) {
                                            // Fallback if date is present but not a DateTime object (e.g., parsing failed)
                                            echo 'Date: ' . htmlspecialchars((string) $item['date']); // Output as string
                                        } else {
                                            echo 'Date unavailable'; // Default message if date is missing
                                        }
                                        ?>
                                    </h5>
                                    <?php if (isset($item['ID'])): // Use the Product ID from the combined data ?>
                                        <a href="product.php?id=<?php echo htmlspecialchars($item['product_id']); ?>" class="btn btn-primary mt-2" style="display: flex; justify-content: center;">View Product</a>
                                     <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php
    // Add some vertical spacing
    for ($i = 0; $i <= 10; $i++) echo "<br>";
    require_once 'footer.php';
    ?>
</body>
</html>