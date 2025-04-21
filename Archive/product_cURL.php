<!DOCTYPE html>
<html>
<?php
// Retrieve the product ID from the GET parameter.
// To support filename extraction, could check that as a fallback, but dynamic routing typically uses query parameters or URL rewriting.
$productId = $_GET['id'] ?? null;
if (!$productId) {
    die("Error: Product ID not specified.");
}

// Prepare a JSON payload with the product ID.
$postData = json_encode(["id" => $productId]);

/* file_get_contents() Version
// Set up the HTTP context for a POST request with JSON.
$options = [
    "http" => [
        "header"  => "Content-Type: application/json\r\n",
        "method"  => "POST",
        "content" => $postData
    ]
];
// Send the POST request to Class_products.php and capture the JSON response.
// Has to be absolute path
$response = file_get_contents("http://localhost/Web%20Assignment/Class_products.php", false, stream_context_create($options));
*/

// Initialize cURL session
$ch = curl_init("http://localhost/Web%20Assignment/Class_products.php");
// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
// Execute cURL request and capture response
$response = curl_exec($ch);
// Check for cURL errors
if ($response === false) {
    die("Error: cURL request failed - " . curl_error($ch));
}
// Close cURL session
curl_close($ch);

$data = json_decode($response, true);

// Check if the request was successful.
if (!$data) {
    $errorMessage = $data["error"] ?? "Product not found.";
    http_response_code(404);
    die("Error: " . $errorMessage);
} elseif (!$data["success"]) {
	$errorMessage = $data["error"] ?? "Product NULL.";
}

// Retrieve the product data.
// Assuming that getProductJSON() returns a JSON that, when decoded, contains an array similar to the output of getProduct(), e.g. ['data' => [ ... product fields ... ], 'types' => [ ... ] ]
$productData = $data["data"];
// For convenience, extract the product details stored under a "data" key.
$product = isset($productData["data"]) ? $productData["data"] : $productData;
//$product = $data["data"];

// Set the page title and CSS file based on the product details.
$pageTitle = $product['cardTitle'];
$pageCSS = 'product.css';

// Include the head.php file which should use $pageTitle and $pageCSS.
include 'head.php';
?>
<body style="font-family:Zen Maru Gothic">
    <main class="product_main">
        <div class="container-fluid">
            <!-- First Row: Video and Product Info -->
            <div class="row">
                <!-- Left 75%: YouTube Video Embed -->
                <div class="col-lg-9 col-md-9 col-sm-12">
                    <div class="ratio ratio-16x9"><!--https://www.youtube.com/embed/-->
                        <iframe src="<?= htmlspecialchars($product['YTLink']) ?>" title="YouTube video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    </div>
                </div>
                <!-- Right 25%: Product Title, Image, and Card Text -->
                <div class="col-lg-3 col-md-3 col-sm-12 d-flex flex-column justify-content-between">
                    <!-- Top: Card Title -->
                    <div>
                        <h2><strong><?= htmlspecialchars($product['cardTitle']) ?></strong></h2>
                    </div>
                    <!-- Middle: Product Image -->
                    <div class="my-2 text-center">
                        <img src="<?= htmlspecialchars($product['imageSrc']) ?>" alt="<?= htmlspecialchars($product['imageAlt']) ?>" class="img-fluid">
                    </div>
                    <!-- Bottom: Card Text -->
                    <div>
                        <p><?= htmlspecialchars($product['cardText']) ?></p>
                    </div>
                </div>
            </div>
            <!-- Second Row: Add to Cart Button -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <button id="add-to-cart" class="btn btn-success" data-product-id="<?= htmlspecialchars($product['product_id']) ?>">Add to cart</button>
                </div>
            </div>
            <!-- Third Row: Product Description -->
            <div class="row mt-4">
                <div class="col-12">
                    <p><?= $product['description'] ?></p>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="src/js/cart_button.js" defer></script>
</body>
</html>