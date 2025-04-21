<!DOCTYPE html>
<html>
<?php
// Retrieve the product ID from the URL query parameter.
$productId = $_GET['id'] ?? null;
if (!$productId) die("Error: Product ID not specified in the URL.");

// Construct the URL to fetch the specific product from Class_products.php
$apiUrl = "http://localhost/Web%20Assignment/Class_products.php/product/" . urlencode($productId);
// Initialize cURL session
$ch = curl_init($apiUrl);

// Set cURL options for a GET request
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true); // Use HTTP GET method
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

// Execute cURL request and capture response
$response = curl_exec($ch);
// Check for cURL errors
if ($response === false) die("Error: cURL request failed - " . curl_error($ch));
// Close cURL session
curl_close($ch);

// Decode the JSON response
$product = json_decode($response, true);
// var_dump($product); exit;

// Check if the request was successful and product data was found
if (!$product) {
    http_response_code(404);
    die("Error: Product not found.");
}

// Get the user ID and session ID from the PHP session
$currentUserUid = $_SESSION['user_id'] ?? null; // Use null coalescing for safety
$currentSessionId = session_id(); // Get the current session ID

// Set the page title and CSS file based on the product details.
$pageTitle = $product['cardTitle'] ?? 'Product Details';
$pageCSS = 'product.css';
include 'head.php';
?>
<body style="font-family:Zen Maru Gothic">
    <main class="product_main">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-9 col-md-9 col-sm-12">
                    <div class="ratio ratio-16x9"><iframe src="<?= htmlspecialchars($product['YTLink'] ?? '') ?>" title="YouTube video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-12 d-flex flex-column justify-content-between">
                    <div>
                        <h2><strong><?= htmlspecialchars($product['cardTitle'] ?? '') ?></strong></h2>
                    </div>
                    <div class="my-2 text-center">
                        <img src="<?= htmlspecialchars($product['imageSrc'] ?? '') ?>" alt="<?= htmlspecialchars($product['imageAlt'] ?? '') ?>" class="img-fluid">
                    </div>
                    <div>
                        <p><?= htmlspecialchars($product['cardText'] ?? '') ?></p>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <button id="add-to-cart" class="btn btn-success" 
                        data-product-id="<?= htmlspecialchars($productId) ?>"
                        data-user-id="<?php echo htmlspecialchars($_SESSION['user_id'] ?? ''); ?>"
                        data-session-id="<?php echo htmlspecialchars(session_id() ?? ''); ?>">
                    Add to cart</button>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <p><?= $product['description'] ?? '' ?></p>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="src/js/cart_button.js" defer></script>
</body>
</html>