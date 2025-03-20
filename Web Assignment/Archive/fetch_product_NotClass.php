<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include the database connection
require "db_connect.php";

// Define the table name
$tbname = "tb_products";

// Set the content type to JSON
header("Content-Type: application/json");

// Decode the incoming JSON payload
$input = json_decode(file_get_contents('php://input'), true);

// Define the action to determine the query
$action = $input['action'] ?? null;

// Verify the action is provided
if (!$action) {
    echo json_encode(["error" => "No action parameter provided"]);
    exit;
}

// Handle different cases based on the action
switch ($action) {
    case 'cart': // Handle cart logic
        $productIds = $input['productIds'] ?? []; // Get product IDs from input
        if (empty($productIds)) {
            echo json_encode(["error" => "No product IDs provided"]);
            exit;
        }
        $placeholders = implode(",", array_fill(0, count($productIds), "?")); // Prepare placeholders for the SQL query
        $sql = "
            SELECT 
                product_id, 
                cardTitle, 
                itemPrice AS price, 
                imageSrc, 
                imageAlt, 
                itemPrice = 0 AS isFree 
            FROM 
                $tbname 
            WHERE 
                product_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat("i", count($productIds)), ...$productIds); // Bind parameters dynamically
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode($products); // Output the fetched products
        $stmt->close();
        break;

    case 'product': // Handle fetching details for a specific product
        $page = $input['page'] ?? null; // Get the 'page' parameter
        $userId = $_SESSION['user_id'] ?? null; // Get the logged-in user's ID
        if (!$page) {
            echo json_encode(["error" => "No page parameter provided"]);
            exit;
        }
        $sql = "
            SELECT 
                p.product_id AS pid, 
                p.cardTitle AS name, 
                p.itemPrice AS price, 
                p.itemPrice = 0 AS isFree, 
                EXISTS(
                    SELECT 1 
                    FROM tb_owned_products 
                    WHERE product_id = p.product_id AND user_id = ?
                ) AS isOwned 
            FROM 
                $tbname p 
            WHERE 
                p.cardLink = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $userId, $page); // Bind user ID and page parameters
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            echo json_encode($product); // Output the fetched product
        } else {
            echo json_encode(["error" => "No product found"]);
        }
        $stmt->close();
        break;

    default:// Handle fetching all products for search or default actions
        $sql = "SELECT product_id, cardID, cardTitle, cardText, itemPrice, cardLink, imageSrc, imageAlt 
                FROM $tbname";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = [
                    'id' => $row['cardID'],
                    'name' => $row['cardTitle'],
                    'price' => $row['itemPrice'],
                    'isFree' => $row['itemPrice'] == 0
                ];
            }
            echo json_encode($products);
        } else {
            echo json_encode(['error' => 'No products found']);
        }
        break;
}
// Close the database connection
$conn->close();
?>