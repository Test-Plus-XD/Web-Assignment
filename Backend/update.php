<?php
// Start session if not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include shared API helpers (assumed to contain callInternalApiGet, Post, Patch, DateTimeZone, etc.)
require_once "firestore.php"; // Assumed to provide API helper functions like callInternalApiGet, Post, Patch, DateTimeZone, etc.

// Define INTERNAL_API_BASE_URL if not defined elsewhere
if (!defined('INTERNAL_API_BASE_URL')) {
    define('INTERNAL_API_BASE_URL', 'http://localhost/Web%20Assignment'); // Adjust if needed
}

// Assume callInternalApiGet, callInternalApiPost, and callInternalApiPatch are defined in firestore.php or an included file.
// Basic implementations shown here as placeholders; replace with your actual functions:
if (!function_exists('callInternalApiGet')) { function callInternalApiGet(string $endpoint): ?array { error_log("API Error: callInternalApiGet not found at endpoint: " . $endpoint); return ['error' => 'Not Implemented', 'details' => 'callInternalApiGet function not defined']; } }
if (!function_exists('callInternalApiPost')) { function callInternalApiPost(string $endpoint, array $data): ?array { error_log("API Error: callInternalApiPost not found for endpoint: " . $endpoint); return ['error' => 'Not Implemented', 'details' => 'callInternalApiPost function not defined']; } }
if (!function_exists('callInternalApiPatch')) { function callInternalApiPatch(string $endpoint, array $data): ?array { error_log("API Error: callInternalApiPatch not found for endpoint: " . $endpoint); return ['error' => 'Not Implemented', 'details' => 'callInternalApiPatch function not defined']; } }

// Hardcode expected fields based on sample data, with basic type hints for form generation.
// These serve as a guide and a fail-safe for insert mode if fetched data is unavailable or incomplete.
// These lists exclude Firestore-managed fields (ID, createTime, updateTime) as they are not edited via the form.
$productFields = [
  "imageSrc" => 'text',
  "imageAlt" => 'text',
  "cardText" => 'textarea',
  "isDigital" => 'boolean',
  "stock" => 'number', // Will be hidden for digital products
  "YTLink" => 'text',
  "itemPrice" => 'number',
  "cardTitle" => 'text',
  "description" => 'textarea',
];

$userFields = [
  "uid" => 'text', // Firebase Auth UID - often set externally or on creation, might be read-only in update
  "displayName" => 'text',
  "email" => 'text', // User email - often a primary identifier, might be read-only
  "phoneNumber" => 'text',
  "photoURL" => 'text',
  "emailVerified" => 'boolean', // Typically not editable via form
  "isAnonymous" => 'boolean', // Typically not editable via form
  "isAdmin" => 'boolean',
];

$purchaseFields = [ // Represents purchase data (formerly owned_product)
  "uid" => 'text', // Firebase Auth UID of the user - linked ID
  "session" => 'text',
  "date" => 'datetime', // Special handling for datetime input, default to current HKT
  "product_id" => 'text', // Firestore Document ID of the product - linked ID
];

$allDefinedFields = [
    'product' => $productFields,
    'user' => $userFields,
    'purchase' => $purchaseFields, // Renamed from owned_product based on URL type
];

// Determine record type and document ID from URL parameters.
$type = $_GET['type'] ?? null; // Expected: "product", "user", or "purchase"
$id = $_GET['id'] ?? null;   // Firestore Document ID (null if inserting new record)

// Validate the record type against defined types
if (!$type || !isset($allDefinedFields[$type])) {
  echo "<p class='text-danger'>Error: Invalid or unsupported record type specified.</p>";
  exit;
}

$hardcodedFieldsForType = $allDefinedFields[$type]; // Get the fields relevant to this type based on hardcoded list
$record = []; // Array to hold data for the existing record (initialized as empty for new records)
$apiError = null; // Variable to store any error messages from API fetch operations

// If updating (ID is provided), fetch the existing record data from the internal API
if ($id) {
    // Determine the correct API endpoint for fetching a single document based on the type and ID.
    // Corrected endpoint pattern: /Class_{type}s.php/{type}/{id}
    $apiEndpoint = '/Class_' . $type . 's.php/' . urlencode($type) . '/' . urlencode($id);
    // Call the internal API to fetch the record data
    $apiResponse = callInternalApiGet($apiEndpoint);

    // Check the API response for errors or if the response structure is not as expected
    if (isset($apiResponse['error'])) {
        // An error occurred during the API fetch
        $apiError = "Error fetching existing record: " . ($apiResponse['details']['error'] ?? $apiResponse['details'] ?? 'Unknown API error');
        error_log("update.php: API fetch error for type {$type}, ID {$id}, Endpoint {$apiEndpoint}: " . json_encode($apiResponse));
    } elseif (!is_array($apiResponse) || empty($apiResponse)) {
         // The API call was successful (HTTP status 2xx) but returned data that is not a non-empty array,
         // which is the expected format for a single document's data. This might mean the record was not found.
         $apiError = "Error fetching existing record: API returned unexpected data format or empty response for ID " . htmlspecialchars($id) . ". Record might not exist.";
         error_log("update.php: API fetch returned unexpected data for type {$type}, ID {$id}, Endpoint {$apiEndpoint}: " . json_encode($apiResponse));
    } else {
        // Success: API returned the record data. Assuming it's an array containing the document's fields.
        // The Firestore document ID ('ID') is typically included in this array by the trait.
        $record = $apiResponse; // Assign the fetched data to the $record variable
    }
}

// If there was an API fetch error when trying to get an existing record, display the error message and stop script execution
if ($apiError && $id) {
    echo "<p class='text-danger'>" . htmlspecialchars($apiError) . "</p>";
    exit;
}

// Check if the record is digital for hiding the stock field in product form (only relevant for product type)
// Use strict comparison (=== true || === 1) as Firestore booleans might be true/false
$isDigital = ($type === 'product' && isset($record['isDigital']) && ($record['isDigital'] === true || $record['isDigital'] === 1));
// Timezone for HKT - used for default purchase date
$hkTimezone = new DateTimeZone('Asia/Hong_Kong');

// Handle Form Submission (INSERT or UPDATE)
// Check if the current request is a POST request (indicating form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedData = []; // Array to hold data collected from $_POST, formatted for the API payload
    $apiEndpoint = ''; // Variable to store the target API endpoint for the submission
    $httpMethod = ''; // Variable to store the HTTP method ('POST' or 'PATCH')

    // Collect data from the $_POST superglobal based on the hardcoded defined fields for this record type.
    // Iterate over the keys of the hardcoded fields to know what inputs to expect from the form.
    foreach ($hardcodedFieldsForType as $fieldName => $typeHint) {
        // Skip fields that are auto-set by the backend/Firestore and are not expected as form inputs.
        // The main document ID ('ID'), 'createTime', and 'updateTime' are Firestore-managed.
        if ($fieldName === 'ID' || $fieldName === 'createTime' || $fieldName === 'updateTime') continue; // Skip Firestore managed fields

        // Handle the 'date' field for 'purchase' separately as it's an input field but needs default logic
        if ($type === 'purchase' && $fieldName === 'date') {
             // Get the date string from POST. Use null coalescing to handle missing field (e.g., if input was disabled).
             $dateStringFromPost = $_POST[$fieldName] ?? '';

             // If in Insert mode AND the date field was left empty, default to current HKT time.
             if (!$id && empty($dateStringFromPost)) {
                 $now = new DateTimeImmutable("now", $hkTimezone);
                 // Format the current HKT time as expected by the API (assuming 'd-m-Y H:i:s.u')
                 $submittedData['date'] = $now->format('d-m-Y H:i:s.u');
             } elseif (!empty($dateStringFromPost)) {
                 // If a date string was provided in POST (either in insert or update), use the submitted string.
                 // We assume the API expects this string format and handles parsing/conversion to Firestore Timestamp.
                 $submittedData['date'] = $dateStringFromPost;
             } else {
                 // If in Update mode and the date field was empty in POST, it means the user cleared it.
                 // Sending an empty string or null will signal the API/Firestore how to handle this (e.g., unset field, keep old value).
                 // Let's send the empty string if it was submitted as empty, for the API to decide.
                 $submittedData['date'] = $dateStringFromPost; // This will be ''
             }
             continue; // Skip to next field as 'date' is handled
        }
        // For all other fields (not ID, timestamps, or the special purchase date), get the value from $_POST
        $value = $_POST[$fieldName] ?? null;

        // Type casting and handling based on the defined field type hint from $hardcodedFieldsForType
        if ($typeHint === 'boolean') {
            // Handle boolean fields, assuming they are submitted via radio buttons with values '1' (true) and '0' (false).
            // Convert the submitted value to a PHP boolean true/false.
            // If the field was not in $_POST (e.g., boolean radios not selected), default to false for consistency.
            $submittedData[$fieldName] = ($value === '1' || $value === 1); // Check against both string '1' and integer 1
        } elseif ($typeHint === 'number') {
             // Handle number fields. Cast the submitted value to a float or int.
             // Treat empty strings or null as null for the number field in the API payload.
             if ($value !== null && $value !== '') {
                 // If the value is not empty, check if it is a valid number before casting.
                 if (is_numeric($value)) {
                     // Cast to int if it's a whole number, otherwise float.
                     $submittedData[$fieldName] = (strpos((string)$value, '.') === false) ? (int)$value : (float)$value;
                 } else {
                     // If submitted value is not numeric but not empty, log a warning and send null? Or send original string?
                     // Sending null is safer for number fields in Firestore if input is invalid.
                      error_log("update.php: Non-numeric value submitted for number field '{$fieldName}' for type {$type}, ID {$id}: '{$value}'");
                      $submittedData[$fieldName] = null; // Send null for invalid numeric input
                 }
             } else {
                 // If the submitted value is empty or null, send null for the number field.
                 $submittedData[$fieldName] = null;
             }
        } elseif ($typeHint === 'password') {
            // For password fields, only include the field in submittedData if a non-empty value was submitted.
            // An empty password field submission usually means the user didn't intend to change the password.
            if (!empty($value)) {
                $submittedData[$fieldName] = $value; // Send the submitted password string
            }
            // If value is empty, do NOT add the field to $submittedData, so the API doesn't try to update the password.
        } else { // Default handling for 'text', 'textarea', and other types
            // Treat null/missing POST values as empty strings for text-based types.
            $submittedData[$fieldName] = $value ?? '';
        }
    }

    // Determine API endpoint and HTTP method based on whether we have an ID (update) or not (insert)
    if ($id) {
        // Update existing record: PATCH request to the specific document endpoint.
        // Corrected Endpoint pattern: /Class_{type}s.php/{type}/{id}
        $apiEndpoint = '/Class_' . $type . 's.php/' . urlencode($type) . '/' . urlencode($id);
        $httpMethod = 'PATCH';
    } else {
        // Insert new record: POST request to the collection endpoint.
        // Assumed Endpoint pattern: /Class_{type}s.php/{type}
        $apiEndpoint = '/Class_' . $type . 's.php/' . urlencode($type);
        $httpMethod = 'POST';

        // For 'purchase' insert, validate that 'product_id' and 'uid' are present in submitted data from the form.
        // These are required fields to link the purchase.
        if ($type === 'purchase') {
             if (empty($submittedData['product_id'])) {
                 echo "<p class='text-danger'>Error: Product ID is required for a new purchase.</p>";
                 $record = $submittedData; // Re-populate form with submitted data
                 goto show_form; // Skip API call and show form with error
             }
             if (empty($submittedData['uid'])) {
                 echo "<p class='text-danger'>Error: User ID is required for a new purchase.</p>";
                 $record = $submittedData; // Re-populate form
                 goto show_form; // Skip API call
             }
        }
        // Note: For 'product' and 'user' insert, the API is expected to handle generating the main ID/UID/email if they are not provided in submittedData.
        // The submittedData should contain the other fields.
    }

    // Call the internal API to perform the insert or update operation using the determined method and endpoint.
    $apiResponse = null; // Variable to store the response from the API call

    if ($httpMethod === 'POST') {
        $apiResponse = callInternalApiPost($apiEndpoint, $submittedData);
    } elseif ($httpMethod === 'PATCH') {
        $apiResponse = callInternalApiPatch($apiEndpoint, $submittedData);
    } else {
         // This block should ideally not be reached given the logic above, but included for robustness.
         echo "<p class='text-danger'>Internal Error: Invalid HTTP method determined for API call.</p>";
         error_log("update.php: Internal Error: Invalid HTTP method '{$httpMethod}' determined for API call for type {$type}, ID {$id}.");
         // Re-populate the form with the submitted data in case of internal error before the API call.
         $record = $submittedData;
         goto show_form;
    }

    // Handle API Response After Submission
    if (isset($apiResponse['error'])) {
        // If the API response contains an 'error' key, the operation failed.
        echo "<p class='text-danger'>API Error during " . ($id ? "update" : "insert") . ": " . ($apiResponse['details']['error'] ?? $apiResponse['details'] ?? 'Unknown API error') . "</p>";
        error_log("update.php: API " . $httpMethod . " error for endpoint {$apiEndpoint}: " . json_encode($apiResponse));
        // Re-populate the form with the data the user submitted so they don't lose their input when an error occurs.
        $record = $submittedData;
    } else {
        // If the API response does NOT contain an 'error' key, the operation is considered successful.
        echo "<h2><p class='text-success' style='text-align: center'>" . ucfirst($type) . " " . ($id ? "updated" : "added") . " successfully.</p></h2>";
        // If it was an insert operation, try to get the newly generated document ID from the API response.
        // Assumes the API returns the new document data including its 'ID' field on successful POST.
        if (!$id && isset($apiResponse['ID'])) {
            $id = $apiResponse['ID']; // Update the $id variable with the new document ID
            // Option: Redirect the user to the update page for the newly created record.
            // This updates the URL in the browser and loads the form for the specific new record,
            // allowing for further edits or showing auto-generated fields like creation timestamp.
            // header("Location: dashboard.php?content=update&type=" . urlencode($type) . "&id=" . urlencode($id));
            // exit; // Stop script execution after issuing the redirect header
            // Option: Update the current page's context to the new ID and re-fetch data to populate the form immediately.
            echo "<p><a href='dashboard.php?content=update&type=" . htmlspecialchars($type) . "&id=" . htmlspecialchars($id) . "'>Edit the new record (" . htmlspecialchars($id) . ")</a></p>";
            // Re-fetch the newly created record data from the API to populate the form.
            // Corrected Endpoint pattern: /Class_{type}s.php/{type}/{id}
            $reFetchEndpoint = '/Class_' . $type . 's.php/' . urlencode($type) . '/' . urlencode($id);
            $reFetchResponse = callInternalApiGet($reFetchEndpoint);

            if (isset($reFetchResponse['error']) || !is_array($reFetchResponse) || empty($reFetchResponse)) {
                error_log("update.php: Failed to re-fetch newly created record {$id} for form repopulation after successful insert.");
                $record = $submittedData; // Fallback to submitted data if re-fetch fails
            } else {
                $record = $reFetchResponse; // Use the complete data from the API
            }
        } elseif ($id) {
            // If it was an update operation and successful, re-fetch the record from the API.
            // This ensures the form displays the latest data from the backend, including any fields
            // that might have been modified by the API on update (e.g., 'updateTime').
            // Corrected Endpoint pattern: /Class_{type}s.php/{type}/{id}
            $reFetchEndpoint = '/Class_' . $type . 's.php/' . urlencode($type) . '/' . urlencode($id);
            $reFetchResponse = callInternalApiGet($reFetchEndpoint);
            if (isset($reFetchResponse['error']) || !is_array($reFetchResponse) || empty($reFetchResponse)) {
            $record = $submittedData; // Fallback to submitted data
            } else {
                $record = $reFetchResponse; // Use the latest data from the API
            }
        } else {
             // If it was an insert and successful, but the API did NOT return an ID (unusual for Firestore-backed API).
             // Clear the form for a new entry.
             $record = [];
        }
    }
}
// This label is used with goto statements to jump back here and display the form
// after handling form submission (either validation error or API error).
show_form:
// HTML Form Display
?>
<h2><?= $id ? "Updating" : "Inserting" ?> <?= ucfirst($type) ?><?= $id ? ": " . htmlspecialchars($id) : '' ?></h2>
<form action="dashboard.php?content=update&type=<?= htmlspecialchars($type) ?><?= $id ? "&id=" . htmlspecialchars($id) : '' ?>" id="updateForm" method="post">
    <table class="table table-striped" style="width: 100%;">
        <tbody>
            <?php
            // Determine fields to display inputs for.
            $fieldsToDisplay = array_keys($hardcodedFieldsForType);

            // Include additional fields from fetched record if updating.
            if ($id && is_array($record)) {
            $fieldsToDisplay = array_unique(array_merge($fieldsToDisplay, array_keys($record)));
            sort($fieldsToDisplay); // Optionally sort for consistency.
            }

            // Loop through each field to create table rows with label and input.
            foreach ($fieldsToDisplay as $field):
            // Skip Firestore managed fields from being displayed as editable inputs.
            if (in_array($field, ['ID', 'createTime', 'updateTime'])) continue;

            // Determine if certain fields should be read-only in update mode.
            $readOnly = ($id && $type === 'purchase' && in_array($field, ['uid', 'product_id']));

            // Format the field name into a human-readable label.
            $label = ucwords(preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace("_", " ", $field)));

            // Get the existing value for the field, or default if empty for new purchase dates.
            $value = $record[$field] ?? '';
            if (!$id && $type === 'purchase' && $field === 'date' && $value === '') {
                $now = new DateTimeImmutable("now", $hkTimezone);
                $value = $now->format('d-m-Y H:i:s.u');
            }

            // Determine the input type hint based on field type.
            $typeHint = $hardcodedFieldsForType[$field] ?? null;
            if ($typeHint === null) {
                if (is_bool($value)) $typeHint = 'boolean';
                elseif (is_numeric($value)) $typeHint = 'number';
                elseif (is_string($value) && strlen($value) > 50) $typeHint = 'textarea';
                elseif (is_string($value) && in_array($field, ['email', 'url', 'imageSrc', 'imageAlt'])) $typeHint = 'text';
                else $typeHint = 'text'; // Default to text for other strings.
            }
            ?>
            <tr>
            <td style="width:20%; font-weight:bold; padding:5px;"><?= htmlspecialchars($label) ?></td>
            <td style="width:80%; padding:5px;">
                <?php // Render input field based on type hint ?>
                <?php if ($field === 'stock'): ?>
                <input type="number" name="<?= htmlspecialchars($field) ?>" class="form-control" value="<?= htmlspecialchars($value) ?>" id="stockField" <?= $isDigital ? 'hidden' : '' ?> <?= $readOnly ? 'readonly' : '' ?> step="any">
                <?php if ($isDigital): ?>
                    <input type="hidden" name="<?= htmlspecialchars($field) ?>" value="">
                <?php endif; ?>
                <?php elseif ($typeHint === 'boolean'): ?>
                <div class="btn-group" role="group" aria-label="<?= htmlspecialchars($field) ?>">
                    <input type="radio" class="btn-check" name="<?= htmlspecialchars($field) ?>" id="<?= $field ?>_yes" value="1" <?= ($value === true || $value === 1) ? "checked" : "" ?> <?= $readOnly ? 'disabled' : '' ?>>
                    <label class="btn btn-outline-success" for="<?= $field ?>_yes">Yes</label>
                    <input type="radio" class="btn-check" name="<?= htmlspecialchars($field) ?>" id="<?= $field ?>_no" value="0" <?= ($value === false || $value === 0 || $value === null) ? "checked" : "" ?> <?= $readOnly ? 'disabled' : '' ?>>
                    <label class="btn btn-outline-danger" for="<?= $field ?>_no">No</label>
                </div>
                <?php elseif ($typeHint === 'number'): ?>
                <input type="number" name="<?= htmlspecialchars($field) ?>" id="<?= $field ?>" class="form-control" value="<?= htmlspecialchars($value) ?>" <?= $field === 'stock' && $isDigital ? 'hidden' : '' ?> <?= $readOnly ? 'readonly' : '' ?> step="any">
                <?php if ($field === 'stock' && $isDigital): ?>
                    <input type="hidden" name="<?= htmlspecialchars($field) ?>" value="">
                <?php endif; ?>
                <?php elseif ($typeHint === 'textarea'): ?>
                <textarea name="<?= htmlspecialchars($field) ?>" id="<?= $field ?>" class="form-control" rows="5" <?= $readOnly ? 'readonly' : '' ?>><?= htmlspecialchars($value) ?></textarea>
                <?php elseif ($typeHint === 'password'): ?>
                <input type="password" name="<?= htmlspecialchars($field) ?>" id="<?= $field ?>" class="form-control" value="" <?= $readOnly ? 'readonly' : '' ?>>
                <?php elseif ($typeHint === 'datetime'): ?>
                <input type="text" name="<?= htmlspecialchars($field) ?>" id="<?= $field ?>" class="form-control" value="<?= htmlspecialchars($value) ?>" <?= $readOnly ? 'readonly' : '' ?> placeholder="e.g., 21-04-2025 14:30:00.000000">
                <?php else: ?>
                <input type="text" name="<?= htmlspecialchars($field) ?>" id="<?= $field ?>" class="form-control" value="<?= htmlspecialchars($value) ?>" <?= $readOnly ? 'readonly' : '' ?>>
                <?php endif; ?>
            </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <div style="text-align: center;">
    <button type="submit" class="btn btn-primary"><?= $id ? "Update" : "Add" ?></button><br><br>
    <?php $cancelContent = 'all_' . $type . 's'; ?>
    <button type="button" class="btn btn-warning" onclick="window.location.href='dashboard.php?content=<?= htmlspecialchars($cancelContent) ?>'">Cancel</button>
    </div>
</form>