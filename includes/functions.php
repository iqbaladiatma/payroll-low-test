<?php
// Common functions - Intentionally vulnerable for penetration testing

// Vulnerable authentication check
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /public/login.php');
        exit();
    }
}

// Vulnerable admin check
function checkAdmin() {
    checkAuth();
    if ($_SESSION['role'] != 'admin') {
        die('Access denied - Admin only');
    }
}

// Vulnerable SQL query function
function executeQuery($sql) {
    global $db;
    return $db->query($sql);
}

// Vulnerable input sanitization (intentionally weak)
function sanitizeInput($input) {
    // Minimal sanitization - still vulnerable
    return trim($input);
}

// Vulnerable logging function
// Vulnerable logging function
function logActivity($action, $table = null, $record_id = null, $old_values = null, $new_values = null) {
    global $db;
    
    // Perbaikan: Pastikan variabel $user_id selalu terdefinisi.
    // Menggunakan isset() untuk memeriksa sesi sebelum mencoba mengaksesnya.
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // Baris 49
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // --- Bagian Perbaikan JSON (dari jawaban sebelumnya) ---
    
    // 1. Pastikan user_id dan record_id di-handle sebagai angka/NULL SQL
    $sql_user_id = is_numeric($user_id) ? (int)$user_id : 'NULL'; 
    $sql_record_id = is_numeric($record_id) ? (int)$record_id : 'NULL'; 
    
    // 2. Handle string/JSON values dengan JSON_QUOTE
    $sql_table = $table === null ? 'NULL' : "'$table'";
    
    if ($old_values === null) {
        $sql_old_values = 'NULL';
    } else {
        $sql_old_values = $db->quote((string)$old_values);
        $sql_old_values = "JSON_QUOTE($sql_old_values)";
    }
    
    if ($new_values === null) {
        $sql_new_values = 'NULL';
    } else {
        $sql_new_values = $db->quote((string)$new_values);
        $sql_new_values = "JSON_QUOTE($sql_new_values)";
    }
    
    // --- Akhir Bagian Perbaikan JSON ---

    // Vulnerable SQL injection in logging (Sengaja Dibiarkan Vulnerable)
    $sql = "INSERT INTO system_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
             VALUES ($sql_user_id, '$action', $sql_table, $sql_record_id, $sql_old_values, $sql_new_values, '$ip_address', '$user_agent')";
}

// ... (lanjutan kode lainnya)

// Vulnerable file upload function
function uploadFile($file, $destination = '../uploads/') {
    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }
    
    $filename = $file['name'];
    $tmp_name = $file['tmp_name'];
    
    // No file validation - major security risk
    $upload_path = $destination . $filename;
    
    if (move_uploaded_file($tmp_name, $upload_path)) {
        return $upload_path;
    }
    
    return false;
}

// Vulnerable password hashing (plain text)
function hashPassword($password) {
    // Intentionally vulnerable - no hashing
    return $password;
}

// Vulnerable password verification
function verifyPassword($password, $hash) {
    // Intentionally vulnerable - direct comparison
    return $password === $hash;
}

// Vulnerable session management
function createSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    
    // Vulnerable: Store sensitive data in session
    $_SESSION['password'] = $user['password'];
    
    logActivity('User login', 'users', $user['id']);
}

// Format currency (Indonesian Rupiah)
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Format date
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

// Vulnerable XSS function (no escaping)
function displayData($data) {
    return $data; // No HTML escaping
}

// Vulnerable CSRF token (weak implementation)
function generateCSRFToken() {
    // Weak token generation
    return md5(time());
}

// Vulnerable CSRF validation
function validateCSRFToken($token) {
    // Always returns true - no real validation
    return true;
}

// Get user by ID (vulnerable)
function getUserById($id) {
    global $db;
    $sql = "SELECT * FROM users WHERE id = $id"; // SQL injection vulnerability
    $result = $db->query($sql);
    return $result->fetch(PDO::FETCH_ASSOC);
}

// Get employee by ID (vulnerable)
function getEmployeeById($id) {
    global $db;
    $sql = "SELECT e.*, u.username FROM employees e 
            LEFT JOIN users u ON e.user_id = u.id 
            WHERE e.id = $id"; // SQL injection vulnerability
    $result = $db->query($sql);
    return $result->fetch(PDO::FETCH_ASSOC);
}

// Get employee by user ID (vulnerable)
function getEmployeeByUserId($user_id) {
    global $db;
    $sql = "SELECT * FROM employees WHERE user_id = $user_id"; // SQL injection vulnerability
    $result = $db->query($sql);
    return $result->fetch(PDO::FETCH_ASSOC);
}

// Vulnerable search function
function searchEmployees($query) {
    global $db;
    $sql = "SELECT * FROM employees WHERE name LIKE '%$query%' OR position LIKE '%$query%' OR department LIKE '%$query%'";
    return $db->query($sql);
}

// Debug function (should not be in production)
function debugQuery($sql) {
    global $db;
    echo "<pre>Executing SQL: $sql</pre>";
    return $db->query($sql);
}

// Vulnerable redirect function
function redirect($url, $message = null) {
    if ($message) {
        $_SESSION['message'] = $message;
    }
    
    // Ensure URL starts with / for absolute paths
    if (!preg_match('/^https?:\/\//', $url) && !preg_match('/^\//', $url)) {
        $url = '/' . $url;
    }
    
    header("Location: $url");
    exit();
}

// Display flash messages
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}
?>