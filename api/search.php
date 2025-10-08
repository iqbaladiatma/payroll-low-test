<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkAuth();

$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo '<div class="text-gray-500">Please enter a search term</div>';
    exit();
}

// Vulnerable search query - SQL injection possible
$search_query = "SELECT * FROM employees WHERE name LIKE '%$query%' OR position LIKE '%$query%' OR department LIKE '%$query%'";
$result = $db->query($search_query);

if ($result && $result->rowCount() > 0) {
    echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
    
    while ($employee = $result->fetch(PDO::FETCH_ASSOC)) {
        echo '<div class="bg-white p-4 rounded-lg shadow border">';
        echo '<h4 class="font-semibold text-lg">' . $employee['name'] . '</h4>'; // Vulnerable to XSS
        echo '<p class="text-gray-600">Position: ' . $employee['position'] . '</p>';
        echo '<p class="text-gray-600">Department: ' . $employee['department'] . '</p>';
        echo '<p class="text-green-600 font-semibold">Salary: ' . formatCurrency($employee['salary']) . '</p>';
        echo '<p class="text-sm text-gray-500">Status: ' . ucfirst($employee['status']) . '</p>';
        
        if ($_SESSION['role'] === 'admin') {
            echo '<div class="mt-3 flex space-x-2">';
            echo '<button onclick="editEmployee(' . $employee['id'] . ')" class="bg-blue-500 text-white px-3 py-1 rounded text-sm">Edit</button>';
            echo '<button onclick="deleteEmployee(' . $employee['id'] . ')" class="bg-red-500 text-white px-3 py-1 rounded text-sm">Delete</button>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
} else {
    echo '<div class="text-center py-8">';
    echo '<i class="fas fa-search text-gray-400 text-4xl mb-4"></i>';
    echo '<p class="text-gray-500">No employees found for: "' . $query . '"</p>'; // Vulnerable to XSS
    echo '</div>';
}
?>