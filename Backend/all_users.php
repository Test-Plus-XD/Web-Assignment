<?php
// Include pagination logic from external PHP file
require_once "pagination.php";

// Define the API endpoint URL to fetch all users from the REST API
$apiEndpoint = 'http://localhost/Web%20Assignment/Class_users.php/all';

// Send a GET request to the API endpoint and store the response
$response = file_get_contents($apiEndpoint);

// If the response is false (API call failed), display an error and stop execution
if ($response === false) {
    echo '<div class="alert alert-danger">Failed to fetch user data.</div>';
    return;
}

// Decode the JSON response from the API into an associative array
$decodedResponse = json_decode($response, true);

// Check if the response is valid and is an array (as expected from Class_users.php)
if (!is_array($decodedResponse)) {
    echo '<div class="alert alert-danger">' . (json_last_error_msg() ?: 'Failed to decode user data or invalid response format.') . '</div>';
    return;
}

// Store the raw user data from the decoded API response
$rawUserData = $decodedResponse; // Class_users.php returns the array directly

// Instantiate the FirestoreAdapter class to normalise and reformat user data
$dataFormatter = new FirestoreAdapter();
$dataFormatter->format($rawUserData); // Call its format method

// Generate paginated results from the formatted user data (20 items per page)
$pagination = getPaginationData($dataFormatter, 20);
$userList = $pagination['dataList']; // Store current page user data

// Initialise an array to hold the desired order of columns
$columnOrder = [];
if (!empty($userList[0]) && is_array($userList[0])) {
    // Always place 'uid' as the first column
    $columnOrder[] = 'uid';

    // Add all other keys except 'uid' to preserve their order
    foreach (array_keys($userList[0]) as $key) {
        if ($key !== 'uid') {
            $columnOrder[] = $key;
        }
    }
}
?>
<!-- Render a responsive Bootstrap-styled table -->
<table class="table table-striped table-bordered">
    <thead class="table-dark">
        <tr>
            <?php if (!empty($columnOrder)): ?>
                <?php foreach ($columnOrder as $field): ?>
                    <!-- Convert field names to Title Case with spacing -->
                    <th><?= ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace("_", " ", $field))) ?></th>
                <?php endforeach; ?>
                <th>Actions</th> <!-- Extra column for Edit/Delete buttons -->
            <?php else: ?>
                <th>No User Data</th> <!-- If there's no data, show fallback -->
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($userList)): ?>
            <?php foreach ($userList as $user): ?>
                <!-- Assign each user row a unique ID using the user's UID -->
                <tr id="user-<?= $user['uid'] ?? '' ?>">
                    <?php foreach ($columnOrder as $key): ?>
                        <td>
                            <?php
                                // Get the value for the current key in this user
                                $value = $user[$key] ?? null;
                                // Display image thumbnail for 'photoURL' values
                                if ($key === 'photoURL') {
                                    $url = htmlspecialchars($value ?? '');
                                    echo '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">';
                                    echo '<img src="' . $url . '" alt="User Photo" style="max-width: 100px; max-height: 100px;">';
                                    echo '</a>';
                                // Display 'True' or 'False' for boolean values
                                } elseif (is_bool($value)) {
                                    echo $value ? 'True' : 'False';
                                } else {
                                    echo htmlspecialchars((string)($value ?? '')); // Default output for other fields
                                }
                            ?>
                        </td>
                    <?php endforeach; ?>

                    <!-- Add Edit and Delete buttons with appropriate event handlers -->
                    <td>
                        <button type="button" class="btn btn-sm btn-warning" onclick="editRecord(event, 'user', '<?= $user['ID'] ?? '' ?>')">
                            <i class="bi bi-pen"></i><br> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser('<?= $user['ID'] ?? '' ?>')">
                            <i class="bi bi-trash3"></i><br> Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- If no users exist on this page, span one row across all columns -->
            <tr><td colspan="100%">No users found on this page.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
displayPagination($pagination); // Render the pagination controls below the table
?>