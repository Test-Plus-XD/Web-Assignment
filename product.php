<?php
// Retrieve the product ID from the URL query parameter.
$productId = $_GET['id'] ?? null;
if (!$productId) {
     http_response_code(400); // Bad Request
     die("Error: Product ID not specified in the URL.");
}

// Construct the URL to fetch the specific product from Class_products.php
$apiUrl = "http://localhost/Web%20Assignment/Class_products.php/product/" . urlencode($productId);

// Initialize cURL session
$ch = curl_init($apiUrl);

// Set cURL options for a GET request
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true); // Use HTTP GET method
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]); // Good practice to set, though not strictly needed for GET

// Execute cURL request and capture response
$response = curl_exec($ch);

// Check for cURL errors
if ($response === false) {
    error_log("cURL request failed for product ID " . $productId . ": " . curl_error($ch));
    http_response_code(500); // Internal Server Error
    die("Error: Failed to fetch product data.");
}

// Get HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// Close cURL session
curl_close($ch);

// Decode the JSON response
$product = json_decode($response, true);

// Check if the request was successful (2xx status) and product data was found
if ($httpCode < 200 || $httpCode >= 300 || !$product || (isset($product['error']) && $product['error'])) {
    error_log("Error fetching product ID " . $productId . ". HTTP Status: " . $httpCode . ", Response: " . ($response ? $response : 'No response body'));
    http_response_code($httpCode >= 400 ? $httpCode : 404); // Use actual status if >= 400, else 404
    die("Error: Product not found or failed to retrieve data.");
}

// Set the page title based on the product details.
$pageTitle = $product['cardTitle'] ?? 'Product Details';
$pageCSS = 'product.css';
include_once 'head.php';
?>
<body style="font-family:Zen Maru Gothic">
    <main class="product_main">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-9 col-md-9 col-sm-12">
                    <div class="ratio ratio-16x9">
                         <iframe id="product-yt-link" src="" title="YouTube video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-12 d-flex flex-column justify-content-between">
                    <div>
                         <h2><strong id="product-title"></strong></h2>
                    </div>
                    <div class="my-2 text-center">
                         <img id="product-image" src="" alt="" class="img-fluid">
                    </div>
                    <div>
                         <p id="product-card-text"></p>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 text-center">
                <div class="col-12 text-center">
                    <button id="add-to-cart" class="btn btn-success" 
                        data-product-id="<?= htmlspecialchars($productId) ?>"
                        data-user-id="<?php echo htmlspecialchars($_SESSION['user_id'] ?? ''); ?>"
                        data-session-id="<?php echo htmlspecialchars(session_id() ?? ''); ?>">
                        Add to cart</button>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <p id="product-description"></p>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>

    <script>
        window.productData = <?php echo json_encode($product); ?>;
        console.log("Product data echoed to JavaScript:", window.productData);
    </script>

    <script src="src/js/cart_button.js" defer></script>
</body>
</html>