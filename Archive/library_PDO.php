<!DOCTYPE html>
<html>
<?php
// Set page variables and include the head section
$pageTitle = 'Library';
$pageCSS = 'library.css';
require_once 'head.php';

// Include the database connection
require_once 'Class_db_connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and retrieve their user_id
if (!isset($_SESSION['user_id'])) {
    echo "<div class='text-danger text-center'>No user data found.</div>";
    exit;
}
$user_id = $_SESSION['user_id'];

require_once 'Class_fetch.php';

// Instantiate the Fetch class using the PDO connection
$fetchInstance = new Fetch($conn);
$ownedProducts = $fetchInstance->library($user_id);
?>
<body style="font-family:Zen Maru Gothic">
    <main class="library_main">
        <div class="container">
            <h1>Your Owned Products</h1>
            <div class="row d-flex justify-content-evenly">
                <?php if (!empty($ownedProducts)): ?>
                    <?php foreach ($ownedProducts as $product): ?>
                        <div class="p-3 col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card">
                                <!-- Display product image -->
                                <img src="<?php echo htmlspecialchars($product['imageSrc']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['imageAlt']); ?>">
                                <div class="card-body">
                                    <!-- Display product title -->
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['cardTitle']); ?></h5>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-warning" style="font-size: 1.91vw; background-color: #2500caca;">No products owned yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php 
    // Add some vertical spacing
    for ($i = 0; $i <= 10; $i++) {
        echo "<br>";
    }
    require_once 'footer.php'; 
    ?>
</body>
</html>