<?php
require_once "Class_db_connect.php"; // Include the PDO connection class

class Owned_Products {
    private $conn; // This will hold the PDO connection object
    private $table_name = "tb_owned_products"; // The name of the table

    // Constructor: initializes the class with a PDO connection passed as $DB
    public function __construct($DB) {
        $this->conn = $DB; // Assign the passed PDO connection to the class property
    }

    // Display all owned product records with optional pagination
    public function display($limit = null, $offset = 0) {
        // SQL query with INNER JOIN to get the user and product details
        $query = "SELECT op.*, u.username, p.cardTitle 
                  FROM " . $this->table_name . " AS op
                  INNER JOIN tb_accounts AS u ON op.user_id = u.user_id
                  INNER JOIN tb_products AS p ON op.product_id = p.product_id
                  ORDER BY op.purchased_date DESC";
        
        if ($limit !== null) {
            $query .= " LIMIT :offset, :limit";
        }

        $stmt = $this->conn->prepare($query);
        
        if ($limit !== null) {
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Get a single owned product by user_id and product_id, including dynamic column types.
    public function getOwnedProduct($user_id, $product_id) {
        // Fetch column types dynamically from tb_owned_products
        $columnsQuery = "SHOW COLUMNS FROM " . $this->table_name;
        $columnsStmt = $this->conn->query($columnsQuery);
        $columns = [];
        while ($row = $columnsStmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row['Type'];
        }

        // Retrieve owned product details with JOINs to get username and cardTitle.
        $query = "SELECT op.*, u.username, p.cardTitle 
                  FROM " . $this->table_name . " AS op
                  INNER JOIN tb_accounts AS u ON op.user_id = u.user_id
                  INNER JOIN tb_products AS p ON op.product_id = p.product_id
                  WHERE op.user_id = ? AND op.product_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $product_id]);
        $ownedProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return the owned product data along with the column types array.
        return $ownedProduct ? ['data' => $ownedProduct, 'types' => $columns] : null;
    }

    public function insertOwnedProduct($data) {
        // Prepare the INSERT SQL statement
        $query = "INSERT INTO " . $this->table_name . " (user_id, product_id, session, purchased_date) 
                  VALUES (:user_id, :product_id, :session, :purchased_date)";
        $stmt = $this->conn->prepare($query);

        // Get the user ID and product ID from input data
        $user_id = $data['user_id'];
        $product_id = $data['product_id'];

        // Sanitize the session field if provided, or set to null
        $session = isset($data['session']) ? htmlspecialchars(strip_tags($data['session'])) : null;
        // Use the provided purchased_date or default to the current timestamp
        $purchased_date = isset($data['purchased_date']) ? $data['purchased_date'] : date("Y-m-d H:i:s");

        // Bind parameters to the SQL query
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        if ($session === null) {
            $stmt->bindValue(":session", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":session", $session, PDO::PARAM_STR);
        }
        $stmt->bindParam(":purchased_date", $purchased_date);

        // Execute the statement and return the result
        return $stmt->execute();
    }

    public function updateOwnedProduct($user_id, $product_id, $data) {
        // Prepare the UPDATE SQL statement
        $query = "UPDATE " . $this->table_name . " SET session = :session, purchased_date = :purchased_date 
                  WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $this->conn->prepare($query);

        // Sanitize the session field if provided, or set to null
        $session = isset($data['session']) ? htmlspecialchars(strip_tags($data['session'])) : null;
        // Use the provided purchased_date or default to the current timestamp
        $purchased_date = isset($data['purchased_date']) ? $data['purchased_date'] : date("Y-m-d H:i:s");

        // Bind parameters to the SQL query
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        if ($session === null) {
            $stmt->bindValue(":session", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":session", $session, PDO::PARAM_STR);
        }
        $stmt->bindParam(":purchased_date", $purchased_date);

        // Execute the statement and return the result
        return $stmt->execute();
    }

    public function deleteOwnedProduct($user_id, $product_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id, $product_id]);
    }

    // Function to calculate profit grouped by purchased_date
    public function profit() {
        // Build the SQL query joining tb_owned_products (alias op) with tb_products (alias p)
        // Group results by purchased_date and sum up the itemPrice from tb_products
        $query = "SELECT op.purchased_date, SUM(p.itemPrice) AS total_profit
                  FROM " . $this->table_name . " AS op
                  INNER JOIN tb_products AS p ON op.product_id = p.product_id
                  GROUP BY op.purchased_date
                  ORDER BY op.purchased_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// This block processes AJAX requests for deletion when this file is accessed directly.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['action']) && $input['action'] === 'delete' && isset($input['user_id']) && isset($input['product_id'])) {
        $DB = new Database();
        $conn = $DB->getConnection();
        $record = new Owned_Products($conn);
        $success = $record->deleteOwnedProduct($input['user_id'], $input['product_id']);
        echo json_encode(["success" => $success]);
        exit;
    }
}
?>