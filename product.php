<!DOCTYPE html>
<html>
<?php
// Include the products class and database connection class.
require_once "Class_products.php";

// Retrieve the product ID from the GET parameter.
// To support filename extraction, could check that as a fallback, but dynamic routing typically uses query parameters or URL rewriting.
$productId = $_GET['id'] ?? null;
if (!$productId) {
    die("Error: Product ID not specified.");
}

// Instantiate the Database and Products classes.
$DB = new Database();
$conn = $DB->getConnection();
$products = new Products($conn);

// Use getProduct() to retrieve the product details. This method returns an array with keys 'data' and 'types'.
$result = $products->getProduct($productId);
if (!$result) {
    die("Error: Product not found.");
}
$product = $result['data'];

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