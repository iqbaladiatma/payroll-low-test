<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkAuth();

$table = $_GET['table'] ?? 'employees';
$format = $_GET['format'] ?? 'csv';

// Vulnerable - no proper authorization check for data export
switch ($table) {
    case 'employees':
        $query = "SELECT * FROM employees";
        $filename = 'employees_export';
        break;
    case 'payroll':
        $query = "SELECT ph.*, e.name as employee_name FROM payroll_history ph 
                  JOIN employees e ON ph.employee_id = e.id";
        $filename = 'payroll_export';
        break;
    case 'users':
        // Extremely vulnerable - exports user data including passwords
        $query = "SELECT * FROM users";
        $filename = 'users_export';
        break;
    case 'all':
        // Export everything - major security risk
        $query = "SELECT 'employees' as table_name, id, name as data FROM employees
                  UNION ALL
                  SELECT 'users' as table_name, id, username as data FROM users";
        $filename = 'full_export';
        break;
    default:
        $query = "SELECT * FROM employees";
        $filename = 'export';
}

$result = $db->query($query);

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    
    $data = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data, JSON_PRETTY_PRINT);
    
} elseif ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Get first row for headers
    $first_row = $result->fetch(PDO::FETCH_ASSOC);
    if ($first_row) {
        fputcsv($output, array_keys($first_row));
        fputcsv($output, $first_row);
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    
} else {
    // Default HTML output
    header('Content-Type: text/html');
    echo '<h1>Data Export - ' . ucfirst($table) . '</h1>';
    echo '<table border="1" style="border-collapse: collapse;">';
    
    $first_row = $result->fetch(PDO::FETCH_ASSOC);
    if ($first_row) {
        echo '<tr>';
        foreach (array_keys($first_row) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        echo '<tr>';
        foreach ($first_row as $value) {
            echo '<td>' . htmlspecialchars($value) . '</td>';
        }
        echo '</tr>';
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo '<tr>';
            foreach ($row as $value) {
                echo '<td>' . htmlspecialchars($value) . '</td>';
            }
            echo '</tr>';
        }
    }
    
    echo '</table>';
}

// Log the export activity
logActivity('Data exported', $table, null, null, "format: $format, table: $table");
?>