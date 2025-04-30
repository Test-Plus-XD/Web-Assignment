<?php
// Require firestore.php for DateTimeInterface and constants if needed by the parsing logic (even if not using the trait)
require_once "firestore.php";

// Define the desired order of fields for the table display
$desired_order = [
   'Record ID',
   'User ID',
   'username',
   'email',
   'Product ID',
   'title',
   'itemPrice',
   'session',
   'Purchased_Date'
];

$purchasesList = []; // Array to hold the final combined and structured data
$errorFetchingData = false; // Flag to track if any required API call failed
$errorMessages = []; // Array to store specific error messages for display

// Fetch Data from APIs

// 1. Fetch all purchase records from the Purchases API
$purchases = callInternalApiGet('/Class_purchases.php/all');

// Check if the purchase fetch was successful and the result is an array (assuming /all returns an array of parsed docs)
if (is_array($purchases) && !isset($purchases['error'])) {

    // 2. Fetch all users and all products from their respective APIs
    // Assuming /all endpoints exist for both and they return arrays of parsed documents/records.
    $users = callInternalApiGet('/Class_users.php/all');
    $products = callInternalApiGet('/Class_products.php/all');

    // Check if both user or product fetches were successful and results are arrays
    if ((is_array($users) || isset($users['error'])) &&
        (is_array($products) || isset($products['error']))) {

        // Data Combination and Structuring: Create lookup maps for users and products by their ID for efficient access

        $userLookup = [];
        // Iterate through the fetched users array
        foreach ($users as $user) {
            // Assuming each user record has an 'uid' field containing the Firebase UID
            if (isset($user['uid'])) {
                $userLookup[$user['uid']] = $user; // Map user record by their UID
            } else {
                 error_log("User record missing 'uid' field: " . json_encode($user));
            }
        }

        $productLookup = [];
        // Iterate through the fetched products array
        foreach ($products as $product) {
            // Assuming each product record has an 'ID' field containing the product document ID
            if (isset($product['ID'])) {
                $productLookup[$product['ID']] = $product; // Map product record by their ID
            } else {
                error_log("Product record missing 'ID' field: " . json_encode($product));
            }
        }

        // 3. Iterate through the fetched purchase records to combine data
        foreach ($purchases as $purchase) {
            // Ensure essential fields are present before processing
            if (!isset($purchase['uid'], $purchase['product_id'], $purchase['ID'])) {
                error_log("Skipping purchase record due to missing 'uid', 'product_id', or 'ID': " . json_encode($purchase));
                continue;
            }

            // Try to look up user and product data using the provided IDs
            $user = $userLookup[$purchase['uid']] ?? null;
            $product = $productLookup[$purchase['product_id']] ?? null;

            // Log only if BOTH user and product are missing
            if (!$user && !$product) {
                error_log("Both user and product missing for Purchase ID: " . ($purchase['ID'] ?? 'Unknown') .
                            " | UID: " . ($purchase['uid'] ?? 'Unknown') .
                            " | Product ID: " . ($purchase['product_id'] ?? 'Unknown'));
            }

            // Log cases where user or product is missing
            //if (!$user) error_log("User not found for UID: " . ($purchase['uid'] ?? 'Unknown') . " in purchase ID: " . ($purchase['ID'] ?? 'Unknown'));
            //if (!$product) error_log("Product not found for ID: " . ($purchase['product_id'] ?? 'Unknown') . " in purchase ID: " . ($purchase['ID'] ?? 'Unknown'));

            // Start combining record fields based on $desired_order
            $combinedRecord = [];
            foreach ($desired_order as $field) {
                switch ($field) {
                    case 'Record ID':
                        // Add purchase document ID
                        $combinedRecord[$field] = $purchase['ID'] ?? '';
                        break;
                    case 'User ID':
                        // Add user UID
                        $combinedRecord[$field] = $purchase['uid'] ?? '';
                        break;
                    case 'username':
                        // Add display name or mark as deleted with <del> tag
                        $combinedRecord[$field] = $user ? htmlspecialchars($user['displayName'] ?? 'N/A') : '<del>Deleted User</del>';
                        break;
                    case 'email':
                        // Add email or mark as deleted with <del> tag
                        $combinedRecord[$field] = $user ? htmlspecialchars($user['email'] ?? 'N/A') : '<del>Deleted User</del>';
                        break;
                    case 'Product ID':
                        // Add product ID
                        $combinedRecord[$field] = $purchase['product_id'] ?? '';
                        break;
                    case 'title':
                        // Add product title or mark as removed with <del> tag
                        $combinedRecord[$field] = $product ? htmlspecialchars($product['cardTitle'] ?? 'N/A') : '<del>Removed Product</del>';
                        break;
                    case 'itemPrice':
                        // Add item price or mark as removed with <del> tag
                        $combinedRecord[$field] = $product ? htmlspecialchars($product['itemPrice'] ?? 'N/A') : '<del>Removed Product</del>';
                        break;
                    case 'session':
                        // Add session string (e.g. Stripe or internal reference)
                        $combinedRecord[$field] = htmlspecialchars($purchase['session'] ?? 'N/A');
                        break;
                    case 'Purchased_Date':
                        // Add purchase date (formatted if it is a DateTimeInterface)
                        $dateValue = $purchase['date'] ?? null;
                        $combinedRecord[$field] = ($dateValue instanceof DateTimeInterface) ?
                            $dateValue->format('Y-m-d H:i:s') : htmlspecialchars($dateValue ?? 'N/A');
                        break;
                    default:
                        // Fallback for unexpected field names
                        $combinedRecord[$field] = 'Unknown Field';
                        error_log("Unknown field '" . $field . "' in \$desired_order for purchase record.");
                        break;
                }
            }
            // Always include the purchase document ID for frontend operations like delete/edit
            $combinedRecord['purchase_document_id'] = $purchase['ID'];
            // Add the fully built record to the final array for rendering
            $purchasesList[] = $combinedRecord;
        }
    } else {
        // Set the error flag if fetching users or products failed
        $errorFetchingData = true;
        // Log the specific errors from the API calls
        error_log("Error fetching user or product data from APIs.");
        if (isset($users['error'])) error_log("Users API Error: " . json_encode($users['error']));
        if (isset($products['error'])) error_log("Products API Error: " . json_encode($products['error']));
        // $purchasesList will remain empty as it was initialised
    }
} else {
    // Set the error flag if the initial purchase fetch failed
    $errorFetchingData = true;
    // Log the specific error from the purchase API call
    error_log("Error fetching purchase data from API: " . json_encode($purchases['error'] ?? 'Unknown Error'));
    // $purchasesList will remain empty
}
// HTML Rendering
// The HTML structure for the table remains largely the same, looping through $purchasesList nd $desired_order. The delete button needs to be updated to use the purchase_document_id.
?>
<table class="table table-striped table-bordered">
   <thead class="table-dark">
        <tr>
            <?php
            // Check if there's data to display or if there was an error before rendering headers based on data
            if (!empty($purchasesList)) {
                // Render headers based on the desired order
                foreach ($desired_order as $field):?>
                    <th><?= htmlspecialchars(ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace("_", " ", $field)))) ?></th>
            <?php endforeach; ?>
                    <th>Actions</th>
            <?php } else {
                // If no data or error, display a single header spanning all columns + Actions
                ?>
                <th colspan="<?= count($desired_order) + 1 ?>">No Data Available</th>
            <?php } ?>
        </tr>
   </thead>
   <tbody>
       <?php if (!empty($purchasesList)): ?>
           <?php foreach ($purchasesList as $record): ?>
               <!-- Row ID for potential future use (e.g., removing row after deletion) -->
                 <!-- Using the purchase document ID makes sense for identifying the row -->
               <tr id="purchase-row-<?= htmlspecialchars($record['purchase_document_id']) ?>">
                   <?php
                   // Loop through the desired order and output the corresponding record values
                   foreach ($desired_order as $field):
                       echo "<td>";
                       // Output the value, apply htmlspecialchars for safety
                       // Use the field name to access the value in the $record
                       echo (strip_tags($record[$field], '<del>')); // Use null coalescing for safety
                       echo "</td>";
                   endforeach;
                   ?>
                <td>
                <!-- Delete button - Call JavaScript function with the purchase document ID -->
                <!-- Ensure the purchase_document_id field was added to the $combinedRecord -->
                <?php $currentPurchaseDocumentID = $record['purchase_document_id'] ?? ''; ?>
                <?php if ($currentPurchaseDocumentID): // Only show delete button if we have the ID ?>
                    <button type="button" class="btn btn-sm btn-warning" onclick="editRecord(event, 'purchase', '<?= htmlspecialchars($currentPurchaseDocumentID) ?>')">
                        <i class="bi bi-pen"></i><br> Edit
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deletePurchase('<?= htmlspecialchars($currentPurchaseDocumentID) ?>')">
                        <i class="bi bi-trash3"></i><br> Delete
                    </button>
                <?php else: ?>
                    Delete N/A
                <?php endif; ?>
                </td>
               </tr>
           <?php endforeach; ?>
       <?php else: ?>
           <tr>
               <!-- Display a message if no record found or if there was an error -->
               <td colspan="<?= count($desired_order) + 1 ?>">
                    <?php if ($errorFetchingData): ?>
                        Error loading data from APIs. Please check logs.
                    <?php else: ?>
                        No purchase record found.
                    <?php endif; ?>
                 </td>
           </tr>
       <?php endif; ?>
   </tbody>
</table>