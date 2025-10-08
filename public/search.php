<?php
session_start();
require_once '../config/database.php';
require_once '../src/controllers/EmployeeController.php';

checkAuth();

$query = $_GET['q'] ?? '';
$employeeController = new EmployeeController();
$results = [];

if ($query) {
    $results = $employeeController->searchEmployees($query);
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Search Employees</h1>
        
        <!-- Search Form -->
        <form method="GET" class="mb-6">
            <div class="flex gap-4">
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" 
                       placeholder="Search by name, position, or department..."
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
            </div>
        </form>
        
        <?php if ($query): ?>
        <div class="search-results">
            <h3 class="text-xl font-semibold mb-4">
                Search Results for: "<?php echo $query; ?>" <!-- Vulnerable to XSS for testing -->
            </h3>
            
            <?php if (empty($results)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-search text-4xl mb-4"></i>
                <p>No employees found matching your search.</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($results as $employee): ?>
                <div class="employee-card bg-gray-50 p-4 border rounded-lg">
                    <h4 class="font-semibold text-lg"><?php echo $employee['name']; ?></h4> <!-- Vulnerable to XSS -->
                    <p class="text-gray-600">Position: <?php echo $employee['position']; ?></p>
                    <p class="text-gray-600">Department: <?php echo $employee['department']; ?></p>
                    <p class="text-green-600 font-semibold">Salary: Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?></p>
                    <p class="text-sm text-gray-500">Status: <?php echo ucfirst($employee['status']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>