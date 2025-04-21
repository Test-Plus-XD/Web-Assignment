<?php
// Include the pagination logic from a shared external file
require_once "pagination.php";

// Define the API endpoint URL that returns all products from the PHP REST API
$apiEndpoint = 'http://localhost/Web%20Assignment/Class_products.php/all';

// Fetch the product data from the API endpoint
$response = file_get_contents($apiEndpoint);

// If the API call fails, show an error message and exit early
if ($response === false) {
    echo '<div class="alert alert-danger">Failed to fetch product data from the API endpoint.</div>';
    return;
}

// Decode the JSON response from the API to an associative array
$decodedResponse = json_decode($response, true);

// If decoding fails or if the data is not an array (as expected), show an error
if (!is_array($decodedResponse)) {
    echo '<div class="alert alert-danger">' . (json_last_error_msg() ?: 'Failed to decode product data or invalid response format.') . '</div>';
    return;
}

// Initialise an array that determines the column order of the table
$columnOrder = [];
if (!empty($decodedResponse) && is_array($decodedResponse)) {
    // Take the first product as a sample to extract keys (columns)
    $firstProduct = reset($decodedResponse);
    if (is_array($firstProduct)) {
        $columnOrder = array_keys($firstProduct); // Set field order from the first item
    }
}

// Instantiate the FirestoreAdapter class to normalise Firestore structure into simple arrays
$dataFormatter = new FirestoreAdapter();
$dataFormatter->format($decodedResponse); // Reformat Firestore-like structure into plain product list

// Use pagination utility to divide the formatted product list into pages (20 per page)
$pagination = getPaginationData($dataFormatter, 20);
$productList = $pagination['dataList']; // Extract just the current page's product entries
?>

<table class="table table-striped table-bordered">
    <thead class="table-dark">
        <tr>
            <?php foreach ($columnOrder as $field): ?>
                <th
                    <?php
                        // If the current field is 'description', apply a 20% width
                        if ($field === 'description') {
                            echo 'style="width: 20%;"';
                        }
                        // If the current field is 'cardText', apply a 10% width
                        elseif ($field === 'cardText') {
                            echo 'style="width: 10%;"';
                        }
                    ?>
                >
                    <?= ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace("_", " ", $field))) ?>
                </th>
            <?php endforeach; ?>
            <th>Actions</th> </tr>
    </thead>
    <tbody>
        <?php if (!empty($productList)): ?>
            <?php foreach ($productList as $product): ?>
                <tr id="product-<?= $product['ID'] ?? '' ?>">
                    <?php foreach ($columnOrder as $key): ?>
                        <td>
                            <?php
                                // Fetch the value of the current field
                                $value = $product[$key] ?? null;

                                // Handle nulls and special field formatting:
                                if (is_null($value) || ($key === "stock" && $value === '')) {
                                    echo 'NULL'; // Explicitly display 'NULL' for missing values
                                } elseif ($key === "isDigital") {
                                    // Convert boolean 'isDigital' to human-readable Yes/No
                                    echo $value ? "Yes" : "No";
                                } elseif ($key === "YTLink") {
                                    // Convert YouTube embed link to normal video link, then show icon
                                    $normalYTLink = preg_replace('/https:\/\/www\.youtube\.com\/embed\/([^?]+).*/', 'https://www.youtube.com/watch?v=$1', $value);
                                    echo '<a href="' . htmlspecialchars($normalYTLink) . '" target="_blank" class="btn btn-link p-0">
                                            <i class="bi bi-youtube" style="color: orangered; font-size: 2.2rem;"></i>
                                        </a>';
                                } elseif ($key === "imageSrc") {
                                    // Show thumbnail and path for product image
                                    echo "<img src='" . htmlspecialchars($value) . "' alt='" . htmlspecialchars($product["imageAlt"] ?? "") . "' width='50'>";
                                    echo "<br><div style='max-width: 5vw;'>" . htmlspecialchars($value) . "</div>";
                                } elseif ($key === "stock") {
                                    // Show 'Unlimited' if stock is empty/null, else show value
                                    echo is_null($value) ? "Unlimited" : htmlspecialchars($value);
                                } elseif ($key === "description") {
                                    // Handle long descriptions with collapsible UI
                                    echo "<div style='max-width: 15vw;'>";
                                    if (str_word_count($value) > 25) {
                                        // Split long description into preview and full text
                                        $descId = 'descCollapse' . $product['ID'];
                                        $shortDescId = 'shortDesc' . $product['ID'];
                                        $toggleBtnId = 'toggleBtn' . $product['ID'];
                                        $readLessId = 'readLess' . $product['ID'];
                                        $words = explode(" ", $value);
                                        $truncated = implode(" ", array_slice($words, 0, 25));

                                        // Render truncated preview with Read More button
                                        echo '<span id="' . $shortDescId . '">' . htmlspecialchars($truncated) . '...</span> ';
                                        echo '<a class="btn btn-link p-0" id="' . $toggleBtnId . '" data-product-id="' . $product['ID'] . '" data-action="expand-description">';
                                        echo '<i class="bi bi-arrow-down-circle"></i> Read More</a>';

                                        // Hidden full description block
                                        echo '<div class="mt-2" id="' . $descId . '" hidden>';
                                        echo '<span>' . htmlspecialchars($value) . '</span>';
                                        echo '<br>';
                                        echo '<a class="btn btn-link p-0 text-danger" id="' . $readLessId . '" data-product-id="' . $product['ID'] . '" data-action="collapse-description" hidden>';
                                        echo '<i class="bi bi-arrow-up-circle"></i> Read Less</a>';
                                        echo '</div>';
                                    } else {
                                        // Show short descriptions directly
                                        echo htmlspecialchars($value);
                                    }
                                    echo "</div>";
                                } else {
                                    // Default case for plain values
                                    echo htmlspecialchars($value);
                                }
                            ?>
                        </td>
                    <?php endforeach; ?>

                    <td>
                        <button class="btn btn-sm btn-info" onclick="editRecord(event, 'product', '<?= $product['ID'] ?? '' ?>')">
                            <i class="bi bi-pen"></i><br> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct('<?= $product['ID'] ?? '' ?>')">
                            <i class="bi bi-trash3"></i><br> Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach;
        else: ?>
            <tr>
                <td colspan="100%">No products found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
// Show pagination controls below the table using helper function
displayPagination($pagination);
?>