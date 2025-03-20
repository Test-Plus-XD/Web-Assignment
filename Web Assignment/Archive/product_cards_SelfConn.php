<div class="container">
    <div class="row d-flex justify-content-evenly">
        <?php
        require "Class_db_connect.php"; // Database connection
        $tbname = "tb_products";
        // Query the products table
        $sql = "SELECT product_id, cardID, cardTitle, cardText, itemPrice, cardLink, imageSrc, imageAlt FROM $tbname";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $itemPrice = $row["itemPrice"] == 0 ? "Free" : "HKD " . number_format($row["itemPrice"], 2);
                ?>
                <div class="p-3 col-12 col-sm-12 col-md-6 col-lg-4" id="<?php echo htmlspecialchars($row['cardID']); ?>">
                    <div class="card d-flex flex-column h-100">
                        <img src="<?php echo htmlspecialchars($row['imageSrc']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['imageAlt']); ?>">
                        <div class="card-body" style="flex: 6;">
                            <h4 class="card-title"><?php echo htmlspecialchars($row['cardTitle']); ?></h4>
                            <p class="card-text p-2"><?php echo htmlspecialchars($row['cardText']); ?></p>
                        </div>
                        <div class="card-footer" style="flex: 1;">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item" id="itemPrice">
                                    <div class="card-body" style="align-content:center">
                                        <b><?php echo $itemPrice; ?></b>
                                    </div>
                                </li>
                                <li class="list-group-item" id="cardLink">
                                    <div class="card-body" style="align-content:center">
                                        <a href="<?php echo htmlspecialchars($row['cardLink']); ?>" class="card-link">Details</a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php
            }
        } else {
            // Display a message if no products are available
            echo "<p class='text-center'>No products available.</p>";
        }
        $conn->close(); // Close the database connection
        ?>
    </div>
</div>