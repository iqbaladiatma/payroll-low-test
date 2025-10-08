<?php
// Vulnerable submission form - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Weak authentication check - but ensure user exists in employees table
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? $_GET['user_name'] ?? 'Test User';

// Check if user exists in employees table, if not create a basic entry
try {
    $check_user = $pdo->query("SELECT id, name, email FROM employees WHERE id = $user_id")->fetch(PDO::FETCH_ASSOC);
    if (!$check_user) {
        // Create a basic employee record for this user
        $pdo->exec("INSERT INTO employees (id, name, email, department, position, salary, hire_date) 
                    VALUES ($user_id, '$user_name', 'user$user_id@example.com', 'General', 'Employee', 5000000, CURDATE())");
        echo "<!-- Auto-created employee record for user $user_id -->";
    }
} catch (Exception $e) {
    // Ignore errors for vulnerability testing
}

// Database connection with exposed credentials
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

$message = '';
$error = '';

// Handle form submission with multiple vulnerabilities
if ($_POST) {
    $submission_type = $_POST['submission_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $priority = $_POST['priority'] ?? 'normal';
    
    // File upload vulnerability - no validation
    $attachment_path = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Dangerous file upload - no extension or type checking
        $filename = $_FILES['attachment']['name'];
        $attachment_path = $filename;
        $full_path = $upload_dir . $filename;
        
        // No sanitization - allows path traversal and malicious files
        move_uploaded_file($_FILES['attachment']['tmp_name'], $full_path);
    }
    
    // Handle empty amount value
    $amount_value = empty($amount) ? 'NULL' : $amount;
    
    // SQL injection vulnerability - direct string concatenation
    $sql = "INSERT INTO submissions (employee_id, submission_type, description, start_date, end_date, amount, priority, attachment_path, status, created_at) 
            VALUES ($user_id, '$submission_type', '$description', '$start_date', '$end_date', $amount_value, '$priority', '$attachment_path', 'pending', NOW())";
    
    try {
        $pdo->exec($sql);
        $message = "Submission created successfully! ID: " . $pdo->lastInsertId();
        
        // Log submission with sensitive data exposure
        error_log("New submission: User $user_id submitted $submission_type - $description");
        
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
        // Expose SQL query in error
        $error .= "<br>Query: " . htmlspecialchars($sql);
    }
}

// Get user's previous submissions with SQL injection vulnerability
$user_filter = $_GET['user_filter'] ?? $user_id;
$status_filter = $_GET['status_filter'] ?? '';

$where_conditions = ["employee_id = $user_filter"];
if ($status_filter) {
    $where_conditions[] = "status = '$status_filter'";
}
$where_clause = implode(' AND ', $where_conditions);

$history_sql = "SELECT * FROM submissions WHERE $where_clause ORDER BY created_at DESC LIMIT 10";
$user_submissions = $pdo->query($history_sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Request - BullsCorp Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Debug Panel (Vulnerable) -->
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 mb-4" style="display: <?php echo isset($_GET['debug']) ? 'block' : 'none'; ?>">
        <strong>Debug Info:</strong>
        <ul class="mt-2 text-sm">
            <li>User ID: <?php echo $user_id; ?></li>
            <li>User Name: <?php echo $user_name; ?></li>
            <li>Session Data: <?php echo json_encode($_SESSION); ?></li>
            <li>Last Query: <?php echo $history_sql ?? 'None'; ?></li>
            <li>Upload Directory: ../uploads/ (writable: <?php echo is_writable('../uploads/') ? 'Yes' : 'No'; ?>)</li>
        </ul>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-paper-plane mr-2"></i>Submit Request
            </h1>
            <div class="flex space-x-2">
                <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                </a>
                <a href="?debug=1" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-bug mr-1"></i>Debug Mode
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Submission Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">
                        <i class="fas fa-edit mr-2"></i>New Submission
                    </h2>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <!-- Hidden fields for user impersonation vulnerability -->
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($user_name); ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Submission Type *</label>
                                <select name="submission_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Type</option>
                                    <option value="leave">Leave Request</option>
                                    <option value="overtime">Overtime Request</option>
                                    <option value="expense">Expense Claim</option>
                                    <option value="advance">Salary Advance</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="low">Low</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <input type="date" name="start_date" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <input type="date" name="end_date" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount (if applicable)</label>
                            <input type="number" name="amount" step="0.01" min="0" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter amount in IDR">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                            <textarea name="description" rows="4" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Please provide detailed description of your request..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Attachment</label>
                            <input type="file" name="attachment" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">
                                ⚠️ No file type restrictions - Upload any file type (Vulnerability for testing)
                            </p>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="reset" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                <i class="fas fa-undo mr-1"></i>Reset
                            </button>
                            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                <i class="fas fa-paper-plane mr-1"></i>Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar mr-2"></i>Your Statistics
                    </h3>
                    <div class="space-y-3">
                        <?php
                        $stats_sql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                            FROM submissions WHERE employee_id = $user_id";
                        $stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Submissions:</span>
                            <span class="font-semibold"><?php echo $stats['total']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-yellow-600">Pending:</span>
                            <span class="font-semibold"><?php echo $stats['pending']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-600">Approved:</span>
                            <span class="font-semibold"><?php echo $stats['approved']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-600">Rejected:</span>
                            <span class="font-semibold"><?php echo $stats['rejected']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h3>
                    <div class="space-y-2">
                        <button onclick="fillLeaveRequest()" class="w-full text-left px-3 py-2 text-sm bg-blue-50 hover:bg-blue-100 rounded">
                            <i class="fas fa-calendar-alt mr-2"></i>Quick Leave Request
                        </button>
                        <button onclick="fillOvertimeRequest()" class="w-full text-left px-3 py-2 text-sm bg-purple-50 hover:bg-purple-100 rounded">
                            <i class="fas fa-clock mr-2"></i>Quick Overtime Request
                        </button>
                        <button onclick="fillExpenseClaim()" class="w-full text-left px-3 py-2 text-sm bg-green-50 hover:bg-green-100 rounded">
                            <i class="fas fa-dollar-sign mr-2"></i>Quick Expense Claim
                        </button>
                    </div>
                </div>

                <!-- Vulnerable Admin Panel Access -->
                <?php if (isset($_GET['admin_access'])): ?>
                <div class="bg-red-100 border border-red-400 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-red-800 mb-2">
                        <i class="fas fa-user-shield mr-2"></i>Admin Access
                    </h3>
                    <div class="space-y-2">
                        <a href="../admin/manage_submissions.php" class="block text-sm text-red-700 hover:underline">
                            <i class="fas fa-cogs mr-1"></i>Manage All Submissions
                        </a>
                        <a href="?user_filter=0" class="block text-sm text-red-700 hover:underline">
                            <i class="fas fa-eye mr-1"></i>View All Users' Submissions
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div> 
       <!-- Recent Submissions -->
        <div class="mt-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-history mr-2"></i>Recent Submissions
                    </h2>
                    <div class="flex space-x-2">
                        <select onchange="filterSubmissions(this.value)" class="text-sm px-2 py-1 border rounded">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($user_submissions)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No submissions found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($user_submissions as $submission): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo $submission['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($submission['submission_type']) {
                                            case 'leave': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'overtime': echo 'bg-purple-100 text-purple-800'; break;
                                            case 'expense': echo 'bg-green-100 text-green-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($submission['submission_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($submission['description']); ?>">
                                        <?php echo htmlspecialchars(substr($submission['description'], 0, 50)) . (strlen($submission['description']) > 50 ? '...' : ''); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($submission['status']) {
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'approved': echo 'bg-green-100 text-green-800'; break;
                                            case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($submission['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($submission['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="viewSubmission(<?php echo $submission['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-900" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($submission['status'] === 'pending'): ?>
                                        <button onclick="editSubmission(<?php echo $submission['id']; ?>)" 
                                                class="text-green-600 hover:text-green-900" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button onclick="cancelSubmission(<?php echo $submission['id']; ?>)" 
                                                class="text-red-600 hover:text-red-900" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($submission['attachment_path']): ?>
                                        <a href="../uploads/<?php echo $submission['attachment_path']; ?>" 
                                           target="_blank" class="text-purple-600 hover:text-purple-900" title="Download Attachment">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Vulnerable JavaScript functions
        function fillLeaveRequest() {
            document.querySelector('select[name="submission_type"]').value = 'leave';
            document.querySelector('textarea[name="description"]').value = 'I would like to request leave for personal reasons.';
            document.querySelector('input[name="start_date"]').value = new Date().toISOString().split('T')[0];
        }

        function fillOvertimeRequest() {
            document.querySelector('select[name="submission_type"]').value = 'overtime';
            document.querySelector('textarea[name="description"]').value = 'I need to work overtime to complete urgent project tasks.';
            document.querySelector('input[name="amount"]').value = '50000';
        }

        function fillExpenseClaim() {
            document.querySelector('select[name="submission_type"]').value = 'expense';
            document.querySelector('textarea[name="description"]').value = 'Business expense reimbursement for client meeting.';
            document.querySelector('input[name="amount"]').value = '100000';
        }

        function filterSubmissions(status) {
            const url = new URL(window.location);
            if (status) {
                url.searchParams.set('status_filter', status);
            } else {
                url.searchParams.delete('status_filter');
            }
            window.location = url;
        }

        function viewSubmission(id) {
            // Vulnerable AJAX call - no CSRF protection
            window.open('../admin/get_submission_details.php?id=' + id, '_blank', 'width=800,height=600');
        }

        function editSubmission(id) {
            // Vulnerable direct parameter passing
            window.location = 'edit_submission.php?id=' + id;
        }

        function cancelSubmission(id) {
            if (confirm('Are you sure you want to cancel this submission?')) {
                // Vulnerable AJAX without CSRF token
                $.post('cancel_submission.php', {id: id}, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }, 'json');
            }
        }

        // Auto-save draft functionality (vulnerable to XSS)
        let draftTimer;
        function saveDraft() {
            const formData = new FormData(document.querySelector('form'));
            const draftData = {};
            for (let [key, value] of formData.entries()) {
                draftData[key] = value;
            }
            
            // Store in localStorage (vulnerable to XSS)
            localStorage.setItem('submission_draft', JSON.stringify(draftData));
            console.log('Draft saved:', draftData);
        }

        // Load draft on page load
        document.addEventListener('DOMContentLoaded', function() {
            const draft = localStorage.getItem('submission_draft');
            if (draft) {
                try {
                    const draftData = JSON.parse(draft);
                    for (let [key, value] of Object.entries(draftData)) {
                        const element = document.querySelector(`[name="${key}"]`);
                        if (element && element.type !== 'file') {
                            element.value = value;
                        }
                    }
                } catch (e) {
                    console.error('Error loading draft:', e);
                }
            }
        });

        // Auto-save every 30 seconds
        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('input', function() {
                clearTimeout(draftTimer);
                draftTimer = setTimeout(saveDraft, 2000);
            });
        });

        // Clear draft on successful submission
        document.querySelector('form').addEventListener('submit', function() {
            localStorage.removeItem('submission_draft');
        });

        // Vulnerable debug function
        function debugMode() {
            console.log('User ID:', <?php echo $user_id; ?>);
            console.log('Session:', <?php echo json_encode($_SESSION); ?>);
            console.log('Database queries:', '<?php echo addslashes($history_sql); ?>');
        }

        // Expose sensitive functions globally
        window.debugMode = debugMode;
        window.userId = <?php echo $user_id; ?>;
        window.userName = '<?php echo addslashes($user_name); ?>';
    </script>
</body>
</html>