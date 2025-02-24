<?php
require_once "Class_products.php";
require_once "pagination.php";

$DB = new Database();
$conn = $DB->getConnection();
$products = new Products($conn);

$records_per_page = 3;
// Retrieve pagination data
$pagination = getPaginationData($products, $records_per_page);
$productList = $pagination['dataList'];
?>

<table class="table table-striped table-bordered">
    <thead class="table-dark">
        <tr>
            <?php if (!empty($productList)) {
                foreach (array_keys($productList[0]) as $field): ?>
                    <th><?= ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace("_", " ", $field))) ?></th>
            <?php endforeach; } else { echo '<th>No Data</th>'; } ?>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($productList)):
            foreach ($productList as $product): ?>
                <tr id="product-<?= $product['product_id'] ?>">
                    <?php foreach ($product as $key => $value): ?>
                        <td>
                            <?php 
                            if ($key === "isDigital") {
                                echo $value ? "Yes" : "No";
                            } elseif ($key === "YTLink") {
                                $normalYTLink = preg_replace('/https:\/\/www\.youtube\.com\/embed\/([^?]+).*/', 'https://www.youtube.com/watch?v=$1', $value);
                                echo '<a href="' . htmlspecialchars($normalYTLink) . '" target="_blank" class="btn btn-link p-0">
                                        <i class="bi bi-youtube" style="color: orangered; font-size: 2.2rem;"></i>
                                      </a>';
                            } elseif ($key === "imageSrc") {
                                echo "<img src='" . htmlspecialchars($value) . "' alt='" . htmlspecialchars($product["imageAlt"] ?? "") . "' width='50'>";
                                echo "<br><div style='max-width: 5vw;'>" . htmlspecialchars($value) . "</div>";
                            } elseif ($key === "stock") {
                                echo is_null($value) ? "Unlimited" : htmlspecialchars($value);
                            } elseif ($key === "description") {
                                echo "<div style='max-width: 15vw;'>";
                                if (str_word_count($value) > 25) {
                                    $descId = 'descCollapse' . $product['product_id'];
                                    $shortDescId = 'shortDesc' . $product['product_id'];
                                    $toggleBtnId = 'toggleBtn' . $product['product_id'];
                                    $readLessId = 'readLess' . $product['product_id'];
                                    $words = explode(" ", $value);
                                    $truncated = implode(" ", array_slice($words, 0, 25));
                                    echo '<span id="' . $shortDescId . '">' . htmlspecialchars($truncated) . '...</span> ';
                                    echo '<a class="btn btn-link p-0" id="' . $toggleBtnId . '" data-product-id="' . $product['product_id'] . '" data-action="expand-description">';
                                    echo '<i class="bi bi-arrow-down-circle"></i> Read More</a>';
                                    echo '<div class="mt-2" id="' . $descId . '" hidden>';
                                    echo '<span>' . htmlspecialchars($value) . '</span>';
                                    echo '<br>';
                                    echo '<a class="btn btn-link p-0 text-danger" id="' . $readLessId . '" data-product-id="' . $product['product_id'] . '" data-action="collapse-description" hidden>';
                                    echo '<i class="bi bi-arrow-up-circle"></i> Read Less</a>';
                                    echo '</div>';
                                } else {
                                    echo htmlspecialchars($value);
                                }
                                echo "</div>";
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editRecord(event, 'product', <?= $product['product_id'] ?>)">
                            <i class="bi bi-pen"></i><br> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?= $product['product_id'] ?>)">
                            <i class="bi bi-trash3"></i><br> Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach;
        else: ?>
            <tr>
                <td colspan="5">No products found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
displayPagination($pagination);
?>