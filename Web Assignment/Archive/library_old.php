<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Library';
$pageCSS = 'library.css';
require_once 'head.php';
require 'Class_db_connect.php';

// Check if the user is logged in and retrieve their user_id
if (!isset($_SESSION['user_id'])) {
    echo "<div class='text-danger text-center'>No user data found.</div>";
    exit;
}
// Get user_id from the session
$user_id = $_SESSION['user_id'];
// SQL query to fetch owned products for the logged-in user
$sql = "
    SELECT p.cardTitle, p.imageSrc, p.imageAlt 
    FROM tb_owned_products u
    INNER JOIN tb_products p
    ON u.product_id = p.product_id
    WHERE u.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$ownedProducts = [];
if ($result->num_rows > 0) {
    // Fetch all products into an array for display
    $ownedProducts = $result->fetch_all(MYSQLI_ASSOC);
}
// Disconnect the database
$stmt->close();
$conn->close();
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
                            <img src="<?php echo htmlspecialchars($product['imageSrc']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['imageAlt']); ?>">
                            <div class="card-body">
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
    <?php $a = 0; 
        while($a<=(15)){ 
            echo "<br>"; 
            $a++;}
        require_once 'footer.php'; ?>
</body>
</html>