<?php
// Start session if not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary classes and database connection.
require_once "Class_db_connect.php";
$DB = new Database();
$conn = $DB->getConnection();

// Determine if we are inserting or updating.
$type = $_GET['type'] ?? null; // Expected: "product", "user", or "owned_product"
$id = $_GET['id'] ?? null;      // ID of the record (null if inserting new)

// Validate record type.
if (!$type || !in_array($type, ['product', 'user', 'owned_product'])) {
    echo "<p class='text-danger'>Error: Invalid record type.</p>";
    exit;
}

// Include the correct class based on type.
if ($type === 'product') {
    require_once "Class_products.php";
    $handler = new Products($conn);
} elseif ($type === 'user') {
    require_once "Class_users.php";
    $handler = new Users($conn);
} elseif ($type === 'owned_product') {
    require_once "Class_owned_products.php";
    $handler = new Owned_Products($conn);
}

// If updating, fetch the existing record.
if ($id) {
    if ($type === 'owned_product') {
        // For owned_product, assume $id is in the format "userId-productId".
        list($user_id, $product_id) = explode('-', $id);
        $result = $handler->getOwnedProduct($user_id, $product_id);
        if (!$result) {
            echo "<p class='text-danger'>Error: Record not found.</p>";
            exit;
        }
        $record = $result['data'];
        $fieldTypes = $result['types'];
    } else {
        // For product and user types.
        $result = ($type === 'product') ? $handler->getProduct($id) : $handler->getUser($id);
        if (!$result) {
            echo "<p class='text-danger'>Error: Record not found.</p>";
            exit;
        }
        $record = $result['data'];
        $fieldTypes = $result['types'];
    }
} else {
    $record = []; // Empty array for new entries
    // Dynamically fetch column names for INSERT mode.
    if ($type === 'product') {
        $query = "SHOW COLUMNS FROM tb_products";
        $stmt = $conn->query($query);
        $fields = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Skip auto-increment primary key (assumed to be 'product_id')
            if (isset($row['Extra']) && strpos($row['Extra'], 'auto_increment') !== false) {
                continue;
            }
            $fields[] = $row['Field'];
            $fieldTypes[$row['Field']] = $row['Type'];
        }
    } elseif ($type === 'user') {
        $query = "SHOW COLUMNS FROM tb_accounts";
        $stmt = $conn->query($query);
        $fields = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Skip auto-increment primary key (assumed to be 'user_id')
            if (isset($row['Extra']) && strpos($row['Extra'], 'auto_increment') !== false) {
                continue;
            }
            $fields[] = $row['Field'];
            $fieldTypes[$row['Field']] = $row['Type'];
        }
    } elseif ($type === 'owned_product') {
        $query = "SHOW COLUMNS FROM tb_owned_products";
        $stmt = $conn->query($query);
        $fields = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // For owned_product, do not skip any fields if you want to allow editing of user_id and product_id,
            // though you may want them as hidden inputs.
            $fields[] = $row['Field'];
            $fieldTypes[$row['Field']] = $row['Type'];
        }
    }
    // Use the dynamically fetched fields for form generation.
    $record = [];
}

// Check if the product is digital for hiding the stock field.
$isDigital = ($type === 'product' && isset($record['isDigital']) && $record['isDigital'] == 1);

// Handle form submission (INSERT or UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($type === 'product') {
        $data = [
            'cardTitle'   => $_POST['cardTitle'] ?? '',
            'cardID'      => $_POST['cardID'] ?? '',
            'cardText'    => $_POST['cardText'] ?? '',
            'YTLink'      => $_POST['YTLink'] ?? '',
            'description' => $_POST['description'] ?? '',
            'itemPrice'   => $_POST['itemPrice'] ?? 0,
            'isDigital'   => $_POST['isDigital'] ?? 0,
            'imageSrc'    => $_POST['imageSrc'] ?? '',
            'imageAlt'    => $_POST['imageAlt'] ?? '',
            'stock'       => ($_POST['isDigital'] == 1) ? null : ($_POST['stock'] ?? null)
        ];
 
        if ($id) {
            // Update mode.
            $success = $handler->updateProduct($id, $data);
        } else {
            // Insert mode.
            $id = $handler->insertProduct($data);
            $success = $id !== false;
        }
 
        if ($success) {
            echo "<p class='text-success'>" . ($id ? "Product updated" : "Product added") . " successfully.</p>";
            if ($id) {
                $result = $handler->getProduct($id);
                if ($result) {
                    $record = $result['data'];
                }
            }
        } else {
            echo "<p class='text-danger'>Failed to " . ($id ? "update" : "add") . " product.</p>";
        }
    } elseif ($type === 'user') {
        $data = [
            'fullname' => $_POST['fullname'] ?? '',
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'isAdmin'  => $_POST['isAdmin'] ?? 0
        ];
 
        if ($id) {
            $success = $handler->updateUser($id, $data);
        } else {
            $id = $handler->insertUser($data);
            $success = $id !== false;
        }
 
        if ($success) {
            echo "<p class='text-success'>" . ($id ? "User updated" : "User added") . " successfully.</p>";
            if ($id) {
                $result = $handler->getUser($id);
                if ($result) {
                    $record = $result['data'];
                }
            }
        } else {
            echo "<p class='text-danger'>Failed to " . ($id ? "update" : "add") . " user.</p>";
        }
    } elseif ($type === 'owned_product') {
        // Build data array for owned_product.
        $data = [
            'user_id'       => $_POST['user_id'] ?? '',
            'product_id'    => $_POST['product_id'] ?? '',
            'session'       => $_POST['session'] ?? '',
            // Set purchased_date to the current timestamp.
            'purchased_date'=> date('Y-m-d H:i:s')
        ];
 
        if ($id) {
            // Update mode: For owned_product, $id is the compound key.
            // Assume $id is in the format "userId-productId", so split it.
            list($user_id, $product_id) = explode('-', $id);
            $success = $handler->updateOwnedProduct($user_id, $product_id, $data);
        } else {
            // Insert mode.
            $id = $handler->insertOwnedProduct($data);
            $success = $id !== false;
        }
 
        if ($success) {
            echo "<p class='text-success'>" . ($id ? "Owned product updated" : "Owned product added") . " successfully.</p>";
            if ($id) {
                // For update mode, refetch record using provided keys.
                $successGet = false;
                if ($id && isset($data['user_id']) && isset($data['product_id'])) {
                    $result = $handler->getOwnedProduct($data['user_id'], $data['product_id']);
                    if ($result) {
                        $record = $result['data'];
                        $fieldTypes = $result['types'];
                        $successGet = true;
                    }
                }
                if (!$successGet) {
                    echo "<p class='text-warning'>Failed to re-fetch owned product.</p>";
                }
            }
        } else {
            echo "<p class='text-danger'>Failed to " . ($id ? "update" : "add") . " owned product.</p>";
        }
    }
}
?>
<h2><?= $id ? "Update" : "Add New" ?> <?= ucfirst($type) ?></h2>
<form action="dashboard.php?content=update&type=<?= htmlspecialchars($type) ?><?= $id ? "&id=" . htmlspecialchars($id) : '' ?>" method="post">
    <table style="width: 100%;">
        <tbody>
            <?php 
            // If inserting a new record, use the dynamically fetched $fields; otherwise, use keys from $record.
            $fieldsToUse = array_keys($fieldTypes);
            
            foreach ($fieldsToUse as $field): 
                // Skip primary key and date fields
                if (($type === 'product' && $field === 'product_id') || ($type === 'user' && $field === 'user_id') || ($type === 'owned_product' && $field === 'purchased_date') ) {
                    continue;
                }
                $label = ucwords(preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace("_", " ", $field)));
                $isTextField = isset($fieldTypes[$field]) && stripos($fieldTypes[$field], 'text') !== false;
                $value = $record[$field] ?? '';
            ?>
            <tr>
                <td style="width:20%; font-weight:bold; padding:5px;"><?= htmlspecialchars($label) ?></td>
                <td style="width:80%; padding:5px;">
                    <?php 
                    if ($field === 'isDigital' || $field === 'isAdmin'): ?>
                        <div class="btn-group" role="group" aria-label="<?= htmlspecialchars($field) ?>">
                            <input type="radio" class="btn-check" name="<?= htmlspecialchars($field) ?>" id="<?= $field ?>_yes" value="1" <?= $value ? "checked" : "" ?>>
                            <label class="btn btn-outline-success" for="<?= $field ?>_yes">Yes</label>
 
                            <input type="radio" class="btn-check" name="<?= htmlspecialchars($field) ?>" id="<?= $field ?>_no" value="0" <?= !$value ? "checked" : "" ?>>
                            <label class="btn btn-outline-danger" for="<?= $field ?>_no">No</label>
                        </div>
                    <?php elseif ($field === 'stock'): ?>
                        <input type="number" name="<?= htmlspecialchars($field) ?>" class="form-control" value="<?= htmlspecialchars($value) ?>" id="stockField" <?= $isDigital ? 'hidden' : '' ?>>
                    <?php elseif ($isTextField): ?>
                        <textarea name="<?= htmlspecialchars($field) ?>" class="form-control" rows="5"><?= htmlspecialchars($value) ?></textarea>
                    <?php else: ?>
                        <input type="text" name="<?= htmlspecialchars($field) ?>" class="form-control" value="<?= htmlspecialchars($value) ?>">
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <div style="text-align: center;">
        <button type="submit" class="btn btn-primary"><?= $id ? "Update" : "Add" ?></button><br><br>
        <button type="button" class="btn btn-warning" onclick="window.location.href='dashboard.php?content=<?= htmlspecialchars($type === 'product' ? 'all_products' : 'all_users') ?>'">Cancel</button>
    </div>
</form>