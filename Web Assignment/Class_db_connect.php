<?php
class Database {
    // Database parameters
    private $dbms = "mysql";            // Database Management System (e.g., mysql)
    private $dbhost = "localhost";      // Database host
    private $dbname = "mydb";           // Database name
    private $dbuser = "mydb_user";      // Database user account
    private $dbpassword = "password";   // Database password
    public $conn;                       // PDO connection object

    // Method to get the PDO connection
    public function getConnection() {
        $this->conn = null;            // Initialize the connection to null
        try {
            // Build the DSN string using the class properties
            $dsn = "{$this->dbms}:host={$this->dbhost};dbname={$this->dbname};charset=utf8";
            // Create a new PDO instance using the DSN and credentials.
            // The array of options ensures:
            // - PDO throws exceptions on error.
            // - Default fetch mode is associative array.
            // - Real prepared statements are used (no emulation).
            $this->conn = new PDO(
                $dsn,
                $this->dbuser,
                $this->dbpassword,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Enable exceptions on errors
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Fetch associative arrays by default
                    PDO::ATTR_EMULATE_PREPARES => false                 // Use native prepared statements
                )
            );
        } catch (PDOException $e) {
            // $e means Exception
            // If the connection fails, output an error message and exit.
            echo "Connection failed: " . $e->getMessage();
            exit;
        }
        // Return the established PDO connection.
        return $this->conn;
    }
}
?>