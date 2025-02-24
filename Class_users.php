<?php
require_once "Class_db_connect.php";

class Users {
    private $conn;
    private $table_name = "tb_accounts";

    // Constructor: initializes the class with a PDO connection.
    public function __construct($DB) {
        $this->conn = $DB;
    }

    // Display all users
    public function display($limit = null, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY user_id ASC";
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

    // Count the total number of users in the database.
    public function countAll() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Get a single user by ID
    public function getUser($id) {
        // Get column types dynamically
        $columnsQuery = "SHOW COLUMNS FROM " . $this->table_name;
        $columnsStmt = $this->conn->query($columnsQuery);
        $columns = [];
        while ($row = $columnsStmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row['Type'];
        }

        // Fetch the user record
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Attach column types to the returned data
        return $user ? ['data' => $user, 'types' => $columns] : null;
    }

    // Inserts a new user into the tb_accounts table and returns the new user ID or false on failure
    public function insertUser($data) {
        // Build the INSERT query using named placeholders
        $query = "INSERT INTO " . $this->table_name . " 
                  (fullname, username, password, isAdmin)
                  VALUES (:fullname, :username, :password, :isAdmin)";
        $stmt = $this->conn->prepare($query);

        // Sanitize and bind parameters
        $fullname = htmlspecialchars(strip_tags($data['fullname']));
        $username = htmlspecialchars(strip_tags($data['username']));
        $password = $data['password'];
        $isAdmin  = $data['isAdmin'] ?? 0;

        $stmt->bindParam(":fullname", $fullname);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", md5($password));
        $stmt->bindParam(":isAdmin", $isAdmin);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId(); // Return the new user ID
        } else {
            return false;
        }
    }


    // Update user details (excluding password for security)
    public function updateUser($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                        SET fullname = :fullname, username = :username, isAdmin = :isAdmin 
                        WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);

        // Store sanitized values in variables
        $fullname = htmlspecialchars(strip_tags($data['fullname']));
        $username = htmlspecialchars(strip_tags($data['username']));
        $isAdmin = $data['isAdmin'];
        $user_id = $id; // Separate variable for binding

        // Bind values to the prepared statement
        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':isAdmin', $isAdmin);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // Delete a user
    public function deleteUser($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}

// This block processes AJAX requests for deletion when this file is accessed directly.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['action']) && $input['action'] === 'delete' && isset($input['user_id'])) {
        $DB = new Database();
        $conn = $DB->getConnection();
        $users = new Users($conn);
        $success = $users->deleteUser($input['user_id']);
        echo json_encode(["success" => $success]);
        exit;
    }
}
?>