<?php
// Setup Salary Modal Handler
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hr'])) {
    http_response_code(403);
    exit('Access denied');
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bullscorp_payroll';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle POST request for bulk salary setup
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'setup_salaries') {
    $employee_ids = $_POST['employee_ids'] ?? [];
    $success_count = 0;
    
    foreach ($employee_ids as $emp_id) {
        // Get employee basic salary
        $empQuery = "SELECT salary FROM employees WHERE id = ?";
        $empStmt = $pdo->prepare($empQuery);
        $empStmt->execute([$emp_id]);
        $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employee) {
            $basic_salary = $employee['salary'];
            $allowances = $basic_salary * 0.1; // 10% allowances
            $total_amount = $basic_salary + $allowances;
            
            // Create salary record for current month
            $pay_period = date('Y-m-01'); // First day of current month
            
            // Check if salary record already exists
            $checkQuery = "SELECT id FROM salaries WHERE employee_id = ? AND pay_period = ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$emp_id, $pay_period]);
            
            if ($checkStmt->rowCount() === 0) {
                $salaryQuery = "INSERT INTO salaries (employee_id, pay_period, basic_salary, allowances, overtime_pay, deductions, total_amount, status, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $salaryStmt = $pdo->prepare($salaryQuery);
                $salaryStmt->execute([
                    $emp_id,
                    $pay_period,
                    $basic_salary,
                    $allowances,
                    0, // overtime_pay
                    0, // deductions
                    $total_amount,
                    'pending',
                    $_SESSION['user_id']
                ]);
                $success_count++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully created salary records for $success_count employees",
        'count' => $success_count
    ]);
    exit;
}

// Get employees without salary records
$query = "SELECT e.id, e.employee_code, e.name, e.email, e.department, e.position, e.salary, e.hire_date
          FROM employees e 
          LEFT JOIN salaries s ON e.id = s.employee_id 
          WHERE s.id IS NULL AND e.status = 'active'
          ORDER BY e.created_at DESC";

$employees = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Salary Setup Modal -->
<div id="salarySetupModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-96 overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">
                <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>
                Setup Missing Salary Records
            </h3>
            <button onclick="closeSalaryModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <?php if (empty($employees)): ?>
        <div class="text-center py-8">
            <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
            <h4 class="text-lg font-semibold text-gray-800 mb-2">All Set!</h4>
            <p class="text-gray-600">All active employees have salary records.</p>
        </div>
        <?php else: ?>
        <div class="mb-6">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                    <div>
                        <h4 class="font-semibold text-yellow-800">Missing Salary Records</h4>
                        <p class="text-yellow-700 text-sm">The following <?php echo count($employees); ?> employees don't have salary records setup yet.</p>
                    </div>
                </div>
            </div>

            <form id="salarySetupForm">
                <input type="hidden" name="action" value="setup_salaries">
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="selectAllEmployees" class="mr-2">
                        <span class="font-medium text-gray-700">Select All Employees</span>
                    </label>
                </div>

                <div class="space-y-3 max-h-64 overflow-y-auto">
                    <?php foreach ($employees as $employee): ?>
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg border">
                        <input type="checkbox" name="employee_ids[]" value="<?php echo $employee['id']; ?>" 
                               class="employee-checkbox mr-3">
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h5 class="font-semibold text-gray-800"><?php echo htmlspecialchars($employee['name']); ?></h5>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($employee['employee_code']); ?> • 
                                        <?php echo htmlspecialchars($employee['position']); ?> • 
                                        <?php echo htmlspecialchars($employee['department']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Hired: <?php echo date('M d, Y', strtotime($employee['hire_date'])); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-green-600">
                                        Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">Base Salary</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-between items-center mt-6 pt-4 border-t">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Salary records will be created for the current month with 10% allowances.
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeSalaryModal()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-check mr-2"></i>Setup Salary Records
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Select all functionality
document.getElementById('selectAllEmployees')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Form submission
document.getElementById('salarySetupForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const selectedEmployees = document.querySelectorAll('.employee-checkbox:checked');
    
    if (selectedEmployees.length === 0) {
        alert('Please select at least one employee');
        return;
    }
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Setting up...';
    submitBtn.disabled = true;
    
    fetch('setup_salary_modal.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeSalaryModal();
            // Reload page to update notifications
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to setup salary records'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while setting up salary records');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

function closeSalaryModal() {
    document.getElementById('salarySetupModal').classList.add('hidden');
    document.getElementById('salarySetupModal').classList.remove('flex');
}

function showSalaryModal() {
    document.getElementById('salarySetupModal').classList.remove('hidden');
    document.getElementById('salarySetupModal').classList.add('flex');
}
</script>