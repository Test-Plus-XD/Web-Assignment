<div class="container">
    <div class="row d-flex justify-content-evenly">
        <?php
        // Include the Fetch class (which uses the DB_Connect class internally)
        require "Class_fetch.php";

        // Instantiate the Fetch class using the existing PDO connection ($conn comes from Class_fetch.php)
        $fetchInstance = new Fetch($conn);

        // Call the allProducts() method to retrieve product data as an array
        $products = $fetchInstance->allProducts(); // Now returns an array
        // Fetch product data from Class_fetch.php
        //$products = require "Class_fetch.php";

        // Check if products were fetched successfully
        if (!is_array($products) || empty($products)) {
            // Display a message if no products are available
            echo "<p class='text-center'>No products available.</p>";
        } else {
            // Loop through the products and display them
            foreach ($products as $product) {
                $itemPrice = $product["price"] == 0 ? "Free" : "HKD " . number_format($product["price"], 2); // Format price
                $isDigital = $product["isDigital"] == 0 ? "Physical Product" : "Digital"; // Check if the product is digital or physical
                ?>
                <div class="p-3 col-12 col-sm-12 col-md-6 col-lg-4" id="<?php echo htmlspecialchars($product['id']); ?>">
                    <div class="card d-flex flex-column h-100">
                        <img src="<?php echo htmlspecialchars($product['imageSrc']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['imageAlt']); ?>">
                        <div class="card-body" style="flex: 6;">
                            <h4 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h4>
                            <p class="card-text p-2"><?php echo htmlspecialchars($product['cardText']); ?></p>
                        </div>
                        <div class="card-footer" style="flex: 1;">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item" id="isDigital">
                                    <div class="card-body" style="align-content:center">
                                        <b><?php echo $isDigital; ?></b>
                                    </div>
                                </li>
                                <li class="list-group-item" id="itemPrice">
                                    <div class="card-body" style="align-content:center">
                                        <b><?php echo $itemPrice; ?></b>
                                    </div>
                                </li>
                                <li class="list-group-item" id="cardLink">
                                    <div class="card-body" style="align-content:center">
                                        <a href="product.php?id=<?= htmlspecialchars($product['product_id']); ?>" class="card-link">Details</a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>