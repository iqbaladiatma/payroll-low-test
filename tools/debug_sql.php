<?php
session_start();
require_once './config/database.php';

// Extremely dangerous debug endpoint - should never exist in production
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}

if ($_POST && isset($_POST['query'])) {
    $database = new Database();
    $db = $database->getConnection();
    $sql_query = $_POST['query'];
    
    // Direct SQL execution - extremely dangerous
    try {
        $result = $db->query($sql_query);
        
        if ($result) {
            $output = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $output[] = $row;
            }
            echo json_encode($output, JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['message' => 'Query executed successfully']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Debug Tool - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <strong>⚠️ DANGER:</strong> This is a direct SQL execution tool. Use with extreme caution!
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-6">SQL Debug Console</h1>
            
            <form method="POST" class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">SQL Query:</label>
                <textarea name="query" rows="5" class="w-full border border-gray-300 rounded-lg p-3 font-mono" 
                          placeholder="SELECT * FROM users;"></textarea>
                <button type="submit" class="mt-3 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Execute Query
                </button>
            </form>
            
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold mb-2">Sample Queries:</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• SELECT * FROM users;</li>
                    <li>• SELECT * FROM employees;</li>
                    <li>• SELECT * FROM payroll_history;</li>
                    <li>• SHOW TABLES;</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>