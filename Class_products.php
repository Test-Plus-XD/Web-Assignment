<?php
require_once "Class_db_connect.php";

class Products {
    private $conn;
    private $table_name = "tb_products";

    // Constructor: initializes the class with a PDO connection.
    public function __construct($DB) {
        $this->conn = $DB;
    }

    // Display all products with optional pagination.
    public function display($limit = null, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY product_id ASC";
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

    // Count the total number of products in the database.
    public function countAll() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Get a single product by ID
    public function getProduct($id) {
        // Fetch column types dynamically to determine data types.
        $columnsQuery = "SHOW COLUMNS FROM " . $this->table_name;
        $columnsStmt = $this->conn->query($columnsQuery);
        $columns = [];
        while ($row = $columnsStmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row['Type'];
        }

        // Retrieve product details from the database.
        $query = "SELECT * FROM " . $this->table_name . " WHERE product_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the product is digital, set stock to null.
        if ($product && $product['isDigital']) {
            $product['stock'] = null;
        }

        // Return product data along with column types.
        return $product ? ['data' => $product, 'types' => $columns] : null;
    }

    // Insert a new product into the database.
    public function insertProduct($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (cardTitle, description, cardText, itemPrice, isDigital, cardID, YTLink, imageSrc, imageAlt, stock)
                  VALUES (:cardTitle, :description, :cardText, :itemPrice, :isDigital, :cardID, :YTLink, :imageSrc, :imageAlt, :stock)";

        $stmt = $this->conn->prepare($query);

        // Prepare and sanitize inputs.
        $cardTitle = htmlspecialchars(strip_tags($data['cardTitle']));
        $description = $data['description'] ?? ''; // Allow HTML
        $cardText = $data['cardText'] ?? '';
        $itemPrice = $data['itemPrice'];
        $isDigital = $data['isDigital'];
        $cardID = htmlspecialchars(strip_tags($data['cardID']));
        // Convert the provided YTLink to an embed URL.
        $YTLink = htmlspecialchars(strip_tags($data['YTLink']));
        $YTLink = $this->convertToEmbedURL($YTLink);
        $imageSrc = htmlspecialchars(strip_tags($data['imageSrc']));
        $imageAlt = htmlspecialchars(strip_tags($data['imageAlt']));
        $stock = ($isDigital == 1) ? null : $data['stock']; // If digital, stock must be null; otherwise, use provided stock

        // Bind parameters to SQL query.
        $stmt->bindParam(":cardTitle", $cardTitle);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":cardText", $cardText);
        $stmt->bindParam(":itemPrice", $itemPrice);
        $stmt->bindParam(":isDigital", $isDigital);
        $stmt->bindParam(":cardID", $cardID);
        $stmt->bindParam(":YTLink", $YTLink);
        $stmt->bindParam(":imageSrc", $imageSrc);
        $stmt->bindParam(":imageAlt", $imageAlt);
        if (is_null($stock)) {
            $stmt->bindValue(":stock", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":stock", $stock);
        }

        // Execute the query and return the last inserted ID if successful.
        return $stmt->execute() ? $this->conn->lastInsertId() : false;
    }

    // Update an existing product.
    public function updateProduct($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET cardTitle = :cardTitle, 
                      description = :description, 
                      cardText = :cardText, 
                      cardID = :cardID,
                      YTLink = :YTLink,
                      itemPrice = :itemPrice, 
                      isDigital = :isDigital, 
                      imageSrc = :imageSrc, 
                      imageAlt = :imageAlt, 
                      stock = :stock
                  WHERE product_id = :product_id";

        $stmt = $this->conn->prepare($query);

        // Prepare and sanitize inputs.
        $cardTitle = htmlspecialchars(strip_tags($data['cardTitle']));
        $description = $data['description'] ?? ''; // Allow HTML
        $cardText = $data['cardText'] ?? '';
        $itemPrice = htmlspecialchars(strip_tags($data['itemPrice']));
        $isDigital = $data['isDigital'];
        $cardID = htmlspecialchars(strip_tags($data['cardID']));
        // Convert the provided YTLink to an embed URL.
        $YTLink = htmlspecialchars(strip_tags($data['YTLink']));
        $YTLink = $this->convertToEmbedURL($YTLink);
        $imageSrc = htmlspecialchars(strip_tags($data['imageSrc']));
        $imageAlt = htmlspecialchars(strip_tags($data['imageAlt']));
        $stock = ($isDigital == 1) ? null : $data['stock']; // If digital, stock must be null; otherwise, use provided stock

        // Bind parameters to SQL query.
        $stmt->bindParam(':cardTitle', $cardTitle);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':cardText', $cardText);
        $stmt->bindParam(':cardID', $cardID);
        $stmt->bindParam(':YTLink', $YTLink);
        $stmt->bindParam(':itemPrice', $itemPrice);
        $stmt->bindParam(':isDigital', $isDigital);
        $stmt->bindParam(':imageSrc', $imageSrc);
        $stmt->bindParam(':imageAlt', $imageAlt);
        if (is_null($stock)) {
            $stmt->bindValue(':stock', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':stock', $stock);
        }
        $stmt->bindParam(':product_id', $id);

        return $stmt->execute();
    }

    // Delete a product from the database.
    public function deleteProduct($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // Private method to convert a normal YouTube URL to an embed URL.
    private function convertToEmbedURL($youtubeURL) {
        $parsedURL = parse_url($youtubeURL);
        // Check for valid YouTube host
        if (!isset($parsedURL['host']) || !in_array($parsedURL['host'], ['www.youtube.com', 'youtube.com', 'm.youtube.com', 'youtu.be'])) {
            return $youtubeURL; // Return original if not valid
        }
        // If the URL is in youtu.be format, extract the video ID from the path.
        if ($parsedURL['host'] === 'youtu.be') {
            $videoID = ltrim($parsedURL['path'], '/');
        } else {
            // For standard YouTube URLs, parse query parameters to get 'v'
            if (!isset($parsedURL['query'])) {
                return $youtubeURL;
            }
            parse_str($parsedURL['query'], $queryParams);
            $videoID = $queryParams['v'] ?? null;
        }
        if (!$videoID) {
            return $youtubeURL;
        }
        // Construct and return the embed URL with the specified 'si' parameter.
        return "https://www.youtube.com/embed/" . htmlspecialchars($videoID) . "?si=STe4Z93ENZHn_MAv";
    }
}

/// This block processes AJAX requests when this file is accessed directly.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the incoming JSON payload.
    $input = json_decode(file_get_contents('php://input'), true);
    // Ensure valid JSON
    if ($input) {
        // Check if this is a deletion request.
        if (isset($input['action']) && $input['action'] === 'delete' && isset($input['product_id'])) {
            $DB = new Database();
            $conn = $DB->getConnection();
            $products = new Products($conn);
            $success = $products->deleteProduct($input['product_id']);
            echo json_encode(["success" => $success]);
            exit;
        }
        // Otherwise, check if an 'id' is provided to get a single product.
        elseif (isset($input['id'])) {
            $DB = new Database();
            $conn = $DB->getConnection();
            $products = new Products($conn);
            $product = $products->getProduct($input['id']);
            if ($product && $product !== "null") {
                echo json_encode(["success" => true, "data" => $product]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "error" => "Connection lost."]);
            }
            exit;
        }
    }
}
?>