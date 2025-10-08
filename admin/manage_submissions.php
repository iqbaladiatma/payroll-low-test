<?php
// Vulnerable Submission Management - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// No proper authentication check - vulnerability
$user_id = $_GET['user_id'] ?? $_SESSION['user_id'] ?? 1;
$user_role = $_GET['role'] ?? $_SESSION['role'] ?? 'admin';

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

// Handle form submissions with SQL injection vulnerabilities
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'approve':
            $submission_id = $_POST['submission_id'];
            // Direct SQL injection vulnerability
            $sql = "UPDATE submissions SET status = 'approved', approved_by = $user_id, approved_at = NOW() WHERE id = $submission_id";
            $pdo->exec($sql);
            $message = "Submission approved successfully!";
            break;
            
        case 'reject':
            $submission_id = $_POST['submission_id'];
            $reason = $_POST['reason'] ?? 'No reason provided';
            // SQL injection vulnerability
            $sql = "UPDATE submissions SET status = 'rejected', rejected_by = $user_id, rejected_at = NOW(), rejection_reason = '$reason' WHERE id = $submission_id";
            $pdo->exec($sql);
            $message = "Submission rejected successfully!";
            break;
            
        case 'delete':
            $submission_id = $_POST['submission_id'];
            // No authorization check - anyone can delete
            $sql = "DELETE FROM submissions WHERE id = $submission_id";
            $pdo->exec($sql);
            $message = "Submission deleted successfully!";
            break;
            
        case 'bulk_action':
            $ids = $_POST['submission_ids'] ?? [];
            $bulk_action = $_POST['bulk_action'];
            if (!empty($ids)) {
                $id_list = implode(',', $ids);
                switch ($bulk_action) {
                    case 'approve_all':
                        $sql = "UPDATE submissions SET status = 'approved', approved_by = $user_id WHERE id IN ($id_list)";
                        break;
                    case 'reject_all':
                        $sql = "UPDATE submissions SET status = 'rejected', rejected_by = $user_id WHERE id IN ($id_list)";
                        break;
                    case 'delete_all':
                        $sql = "DELETE FROM submissions WHERE id IN ($id_list)";
                        break;
                }
                $pdo->exec($sql);
                $message = "Bulk action completed successfully!";
            }
            break;
    }
}

// Search functionality with SQL injection
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';

$where_conditions = [];
if ($search) {
    // Direct string concatenation - SQL injection vulnerability
    $where_conditions[] = "(employee_name LIKE '%$search%' OR submission_type LIKE '%$search%' OR description LIKE '%$search%')";
}
if ($filter_status) {
    $where_conditions[] = "status = '$filter_status'";
}
if ($filter_type) {
    $where_conditions[] = "submission_type = '$filter_type'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get submissions with vulnerable query - fix JOIN issue
$sql = "SELECT s.*, 
        COALESCE(e.name, CONCAT('User #', s.employee_id)) as employee_name, 
        COALESCE(e.email, 'No email') as employee_email 
        FROM submissions s 
        LEFT JOIN employees e ON s.employee_id = e.id 
        $where_clause 
        ORDER BY s.created_at DESC";

$submissions = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM submissions";
$stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Submissions - BullsCorp Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Debug Info Panel (Vulnerable) -->
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mb-4" style="display: <?php echo isset($_GET['debug']) ? 'block' : 'none'; ?>">
        <strong>Debug Info:</strong>
        <ul class="mt-2 text-sm">
            <li>User ID: <?php echo $user_id; ?></li>
            <li>User Role: <?php echo $user_role; ?></li>
            <li>Database: <?php echo $db_name; ?></li>
            <li>Last Query: <?php echo $sql ?? 'None'; ?></li>
            <li>POST Data: <?php echo json_encode($_POST); ?></li>
            <li>GET Data: <?php echo json_encode($_GET); ?></li>
        </ul>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-clipboard-list mr-2"></i>Manage Submissions
            </h1>
            <div class="flex space-x-2">
                <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                </a>
                <a href="?debug=1" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-bug mr-1"></i>Debug Mode
                </a>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Submissions</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-check text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Approved</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['approved']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-times text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Rejected</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['rejected']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name, type, or description..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="leave" <?php echo $filter_type === 'leave' ? 'selected' : ''; ?>>Leave Request</option>
                        <option value="overtime" <?php echo $filter_type === 'overtime' ? 'selected' : ''; ?>>Overtime</option>
                        <option value="expense" <?php echo $filter_type === 'expense' ? 'selected' : ''; ?>>Expense Claim</option>
                        <option value="other" <?php echo $filter_type === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-search mr-1"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="POST" id="bulkForm">
                <input type="hidden" name="action" value="bulk_action">
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="selectAll" class="mr-2">
                        <span class="text-sm font-medium">Select All</span>
                    </label>
                    
                    <select name="bulk_action" class="px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Choose Action</option>
                        <option value="approve_all">Approve Selected</option>
                        <option value="reject_all">Reject Selected</option>
                        <option value="delete_all">Delete Selected</option>
                    </select>
                    
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-bolt mr-1"></i>Execute
                    </button>
                </div>
            </form>
        </div> 
       <!-- Submissions Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" class="selectAll">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>No submissions found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="submission_ids[]" value="<?php echo $submission['id']; ?>" class="submissionCheck">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #<?php echo $submission['id']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($submission['employee_name'] ?: 'Unknown User'); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($submission['employee_email'] ?: 'No email'); ?>
                                        </div>
                                    </div>
                                </div>
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
                                    <i class="fas fa-<?php 
                                        switch($submission['submission_type']) {
                                            case 'leave': echo 'calendar-alt'; break;
                                            case 'overtime': echo 'clock'; break;
                                            case 'expense': echo 'dollar-sign'; break;
                                            default: echo 'file';
                                        }
                                    ?> mr-1"></i>
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
                                    <i class="fas fa-<?php 
                                        switch($submission['status']) {
                                            case 'pending': echo 'clock'; break;
                                            case 'approved': echo 'check'; break;
                                            case 'rejected': echo 'times'; break;
                                            default: echo 'question';
                                        }
                                    ?> mr-1"></i>
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($submission['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <?php if ($submission['status'] === 'pending'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <button type="submit" class="text-green-600 hover:text-green-900" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    
                                    <button onclick="showRejectModal(<?php echo $submission['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="showDetailsModal(<?php echo $submission['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this submission?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
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

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Submission</h3>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="submission_id" id="rejectSubmissionId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
                    <textarea name="reason" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Please provide a reason for rejection..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideRejectModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                        Reject Submission
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Submission Details</h3>
            <div id="detailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="flex justify-end mt-4">
                <button onclick="hideDetailsModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Vulnerable JavaScript with XSS possibilities
        function showRejectModal(submissionId) {
            document.getElementById('rejectSubmissionId').value = submissionId;
            document.getElementById('rejectModal').classList.remove('hidden');
            document.getElementById('rejectModal').classList.add('flex');
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectModal').classList.remove('flex');
        }

        function showDetailsModal(submissionId) {
            // Vulnerable AJAX call - no CSRF protection
            $.get('get_submission_details.php?id=' + submissionId, function(data) {
                document.getElementById('detailsContent').innerHTML = data;
                document.getElementById('detailsModal').classList.remove('hidden');
                document.getElementById('detailsModal').classList.add('flex');
            });
        }

        function hideDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
            document.getElementById('detailsModal').classList.remove('flex');
        }

        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.submissionCheck');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Bulk form submission
        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.submissionCheck:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one submission');
                return;
            }
            
            // Add checked IDs to form
            checkedBoxes.forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'submission_ids[]';
                hiddenInput.value = checkbox.value;
                this.appendChild(hiddenInput);
            });
        });

        // Auto-refresh every 30 seconds (vulnerable to manipulation)
        setInterval(function() {
            if (window.location.search.indexOf('auto_refresh=1') !== -1) {
                window.location.reload();
            }
        }, 30000);
    </script>
</body>
</html>