<?php
// Database configuration - Intentionally vulnerable for penetration testing

class Database {
    private $host = 'localhost';
    private $db_name = 'bullscorp_payroll';
    private $username = 'root';
    private $password = '';
    public $conn;

    // Vulnerable connection - no error handling, credentials exposed
    public function getConnection() {
        $this->conn = null;
        
        try {
            // Vulnerable: Using old mysql extension style for compatibility
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            
            // Vulnerable: Disable prepared statements for SQL injection testing
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            
        } catch(PDOException $exception) {
            // Vulnerable: Exposing database errors
            echo "Connection error: " . $exception->getMessage();
            die();
        }

        return $this->conn;
    }
    
    // Vulnerable direct query method
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    // Vulnerable execute method
    public function exec($sql) {
        return $this->conn->exec($sql);
    }
}

// Global database instance (bad practice)
$database = new Database();
$db = $database->getConnection();
?>