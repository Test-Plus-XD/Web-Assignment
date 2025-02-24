<?php
require_once "Class_Owned_Products.php";
require_once "pagination.php";

$DB = new Database();
$conn = $DB->getConnection();
$record = new Owned_Products($conn);

$records_per_page = 10;
// Retrieve pagination data
$pagination = getPaginationData($record, $records_per_page);
$ownedProductsList = $pagination['dataList'];
$desired_order = [
    'user_id',
    'username',
    'product_id',
    'cardTitle',
    'session',
    'purchased_date'
];
?>

<table class="table table-striped table-bordered">
    <thead class="table-dark">
        <tr>
            <?php if (!empty($ownedProductsList)) {
                foreach ($desired_order as $field): ?>
                    <th><?= ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace("_", " ", $field))) ?></th>
            <?php endforeach; } else { echo '<th>No Data</th>'; } ?>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($ownedProductsList)):
            foreach ($ownedProductsList as $record): ?>
                <tr id="ownedProduct-<?= $record['user_id'] ?>-<?= $record['product_id'] ?>">
                    <?php 
                    // Loop through the desired order and output the corresponding record values
                    foreach ($desired_order as $field):
                        echo "<td>";
                        // Check if the field is "session" and handle it appropriately
                        if ($field === "session") {
                            echo ($record[$field] !== "" ? htmlspecialchars($record[$field]) : "N/A");
                        } else {
                            echo htmlspecialchars($record[$field]);
                        }
                        echo "</td>";
                    endforeach;
                    ?>
                    <td>
                        <!-- <button class="btn btn-sm btn-info" onclick="editRecord(event, 'ownedProduct', <?= $record['user_id'] ?>, <?= $record['product_id'] ?>)">
                            <i class="bi bi-pen"></i><br> Edit
                        </button> -->
                        <button class="btn btn-sm btn-danger" onclick="deleteOwnedProduct(<?= $record['user_id'] ?>, <?= $record['product_id'] ?>)">
                        <i class="bi bi-trash3"></i><br> Delete
                    </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No owned products found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
displayPagination($pagination);
?>