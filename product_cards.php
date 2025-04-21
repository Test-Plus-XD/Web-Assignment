<div class="container">
    <div class="row d-flex justify-content-evenly">
        <?php
        // URL to Class_products.php endpoint for fetching all products
        $apiEndpoint = 'http://localhost/Web%20Assignment/Class_products.php/all';

        // Fetch JSON data from the API
        $jsonData = file_get_contents($apiEndpoint);

        // Decode the JSON data into a PHP associative array
        $products = json_decode($jsonData, true);
        // var_dump($products); exit;

        // Check if data was fetched successfully and is an array
        if (is_array($products)) {
            // Loop through the products and display them
            foreach ($products as $product) {
                $cardTitle = isset($product['cardTitle']) ? htmlspecialchars($product['cardTitle']) : '';
                $priceValue = isset($product['itemPrice']) ? $product['itemPrice'] : 0;
                $itemPrice = $priceValue == 0 ? "Free" : "HKD " . number_format($priceValue, 2);
                $isDigitalValue = isset($product['isDigital']) ? $product['isDigital'] : false;
                $isDigital = $isDigitalValue ? "Digital" : "Physical Product";
                $imageSrc = isset($product['imageSrc']) ? htmlspecialchars($product['imageSrc']) : '';
                $imageAlt = isset($product['imageAlt']) ? htmlspecialchars($product['imageAlt']) : '';
                $cardText = isset($product['cardText']) ? htmlspecialchars($product['cardText']) : '';
                $productId = isset($product['ID']) ? htmlspecialchars($product['ID']) : ''; // Use the 'ID' key here
                ?>
                <div class="p-3 col-12 col-sm-12 col-md-6 col-lg-4" id="<?php echo $productId; ?>">
                    <div class="card d-flex flex-column h-100">
                        <img src="<?php echo $imageSrc; ?>" class="card-img-top" alt="<?php echo $imageAlt; ?>">
                        <div class="card-body" style="flex: 6;">
                            <h4 class="card-title"><?php echo $cardTitle; ?></h4>
                            <p class="card-text p-2"><?php echo $cardText; ?></p>
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
                                        <a href="product.php?id=<?= $productId; ?>" class="card-link">Details</a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<p class='text-center'>Failed to load products.</p>";
            if ($jsonData) {
                echo "<pre>";
                echo htmlspecialchars($jsonData);
                echo "</pre>";
            }
        }
        ?>
    </div>
</div>