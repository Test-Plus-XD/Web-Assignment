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

    // Check if both user and product fetches were successful and results are arrays
    if (is_array($users) && !isset($users['error']) &&
        is_array($products) && !isset($products['error'])) {

        // Data Combination and Structuring

        // Create lookup maps for users and products by their ID for efficient access
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

        // Iterate through the fetched purchase records to combine data
        foreach ($purchases as $purchase) {
            // Ensure essential IDs exist in the purchase record before processing
            // 'uid' links to the user, 'product_id' links to the product, 'ID' is the purchase document ID for deletion
            if (!isset($purchase['uid'], $purchase['product_id'], $purchase['ID'])) {
                error_log("Skipping purchase record due to missing 'uid', 'product_id', or 'ID': " . json_encode($purchase));
                continue; // Skip this purchase record if essential data is missing
            }

            // Look up the user and product details using the IDs from the purchase record
            $user = $userLookup[$purchase['uid']] ?? null; // Get user details, null if not found
            $product = $productLookup[$purchase['product_id']] ?? null; // Get product details, null if not found

            // Only include this purchase record in the final list if both corresponding user and product details were found
            if ($user && $product) {
                // Construct the final record structure based on $desired_order
                $combinedRecord = [];
                foreach ($desired_order as $field) {
                    switch ($field) {
                        case 'Record ID':
                            // Map the document ID from the purchase record
                            $combinedRecord[$field] = $purchase['ID'] ?? '';
                            break;
                        case 'User ID':
                            // Map the user ID from the purchase record
                            $combinedRecord[$field] = $purchase['uid'] ?? '';
                            break;
                        case 'username':
                            // Map the username display name or email from the looked-up user record
                             $combinedRecord[$field] = $user['displayName'] ?? 'N/A';
                            break;
                        case 'email':
                            // Map the username  email from the looked-up user record
                             $combinedRecord[$field] = $user['email'] ?? 'N/A';
                            break;
                        case 'Product ID':
                            // Map the product ID from the purchase record
                            $combinedRecord[$field] = $purchase['product_id'] ?? '';
                            break;
                        case 'title':
                            // Map the card title from the looked-up product record
                            $combinedRecord[$field] = $product['cardTitle'] ?? 'N/A';
                            break;
                        case 'itemPrice':
                            // Map the itemPrice from the looked-up product record
                            $combinedRecord[$field] = $product['itemPrice'] ?? 'N/A';
                            break;
                        case 'session':
                            // Map the session ID from the purchase record
                            $combinedRecord[$field] = $purchase['session'] ?? 'N/A';
                            break;
                        case 'Purchased_Date':
                            // Map the date from the purchase record, format if it's a DateTimeInterface object
                            $dateValue = $purchase['date'] ?? null;
                            $combinedRecord[$field] = ($dateValue instanceof DateTimeInterface) ?
                                $dateValue->format('Y-m-d H:i:s') : ($dateValue ?? 'N/A');
                            break;
                        default:
                            // Handle any unexpected fields defined in $desired_order
                            $combinedRecord[$field] = 'Unknown Field';
                            error_log("Unknown field '" . $field . "' in \$desired_order for purchase record.");
                            break;
                    }
                }
                // Add the original purchase document ID to the combined record for the delete button
                $combinedRecord['purchase_document_id'] = $purchase['ID'];
                // Add the completed combined record to the list
                $purchasesList[] = $combinedRecord;
            } else {
                 // Log if user or product details were missing for a specific purchase record, indicating incomplete data
                 error_log("Missing user or product details for purchase UID: " . ($purchase['uid'] ?? 'Unknown') . ", Product ID: " . ($purchase['product_id'] ?? 'Unknown') . ". Purchase ID: " . ($purchase['ID'] ?? 'Unknown') . ". Skipping record.");
            }
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
                       echo htmlspecialchars($record[$field] ?? ''); // Use null coalescing for safety
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