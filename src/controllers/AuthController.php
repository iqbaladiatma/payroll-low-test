<?php
// Authentication Controller - Handles login, register, logout
require_once __DIR__ . '/../../config/database.php';

class AuthController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Login functionality
    public function login($username, $password) {
        try {
            // Get user by username or email
            $query = "SELECT * FROM users WHERE username = ? OR email = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$username, $username]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Check password (support both hashed and plain text for testing)
                $password_valid = false;
                
                // Check hashed password
                if (password_verify($password, $user['password'])) {
                    $password_valid = true;
                }
                
                // Vulnerability: Also accept plain text passwords for testing
                if ($user['password'] === $password) {
                    $password_valid = true;
                }
                
                if ($password_valid) {
                    // Session already started, just set variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['employee_id'] = $user['employee_id'];
                    
                    // Check if user needs to complete profile
                    if (!$user['employee_id']) {
                        $_SESSION['needs_profile_completion'] = true;
                    }
                    
                    return true;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    // Register functionality
    public function register($username, $password, $email, $role = 'employee') {
        try {
            // Check if username or email already exists
            $checkQuery = "SELECT id FROM users WHERE username = ? OR email = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$username, $email]);
            
            if ($checkStmt->rowCount() > 0) {
                return false; // User already exists
            }
            
            // Hash password for security (but keep plain text option for testing)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user - sesuai dengan struktur tabel users yang ada
            $query = "INSERT INTO users (username, password, email, role, employee_id) VALUES (?, ?, ?, ?, NULL)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$username, $hashedPassword, $email, $role]);
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }
    
    // Logout functionality
    public function logout() {
        session_start();
        session_destroy();
        return true;
    }
}
?>