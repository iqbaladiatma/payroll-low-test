<?php
// Get Submission Details - Vulnerable for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

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
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get submission ID from URL (vulnerable to manipulation)
$submission_id = $_GET['id'] ?? 0;

// Fetch submission details with SQL injection vulnerability
$sql = "SELECT s.*, 
        COALESCE(e.name, CONCAT('User #', s.employee_id)) as employee_name, 
        COALESCE(e.email, 'No email') as employee_email,
        COALESCE(e.department, 'Unknown') as department
        FROM submissions s 
        LEFT JOIN employees e ON s.employee_id = e.id 
        WHERE s.id = $submission_id";

try {
    $result = $pdo->query($sql);
    $submission = $result->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        echo "<p class='text-red-600'>Submission not found!</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p class='text-red-600'>Database error: " . $e->getMessage() . "</p>";
    echo "<p class='text-xs text-gray-500'>Query: " . htmlspecialchars($sql) . "</p>";
    exit;
}
?>

<div class="space-y-4">
    <!-- Employee Information -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-800 mb-2">Employee Information</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-600">Name:</span>
                <span class="ml-2 font-medium"><?php echo htmlspecialchars($submission['employee_name']); ?></span>
            </div>
            <div>
                <span class="text-gray-600">Email:</span>
                <span class="ml-2 font-medium"><?php echo htmlspecialchars($submission['employee_email']); ?></span>
            </div>
            <div>
                <span class="text-gray-600">Employee ID:</span>
                <span class="ml-2 font-medium">#<?php echo $submission['employee_id']; ?></span>
            </div>
            <div>
                <span class="text-gray-600">Department:</span>
                <span class="ml-2 font-medium"><?php echo htmlspecialchars($submission['department']); ?></span>
            </div>
        </div>
    </div>

    <!-- Submission Details -->
    <div class="bg-blue-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-800 mb-2">Submission Details</h4>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Submission ID:</span>
                <span class="font-medium">#<?php echo $submission['id']; ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Type:</span>
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
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Priority:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    <?php 
                    switch($submission['priority']) {
                        case 'low': echo 'bg-gray-100 text-gray-800'; break;
                        case 'normal': echo 'bg-blue-100 text-blue-800'; break;
                        case 'high': echo 'bg-orange-100 text-orange-800'; break;
                        case 'urgent': echo 'bg-red-100 text-red-800'; break;
                        default: echo 'bg-gray-100 text-gray-800';
                    }
                    ?>">
                    <?php echo ucfirst($submission['priority']); ?>
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Status:</span>
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
            </div>
            <?php if ($submission['start_date']): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">Start Date:</span>
                <span class="font-medium"><?php echo date('M d, Y', strtotime($submission['start_date'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($submission['end_date']): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">End Date:</span>
                <span class="font-medium"><?php echo date('M d, Y', strtotime($submission['end_date'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($submission['amount']): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">Amount:</span>
                <span class="font-medium">Rp <?php echo number_format($submission['amount'], 0, ',', '.'); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Description -->
    <div class="bg-white border p-4 rounded-lg">
        <h4 class="font-semibold text-gray-800 mb-2">Description</h4>
        <div class="text-sm text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($submission['description']); ?></div>
    </div>

    <!-- Attachment -->
    <?php if ($submission['attachment_path']): ?>
    <div class="bg-purple-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-800 mb-2">Attachment</h4>
        <div class="flex items-center space-x-2">
            <i class="fas fa-paperclip text-purple-600"></i>
            <a href="../uploads/<?php echo $submission['attachment_path']; ?>" target="_blank" 
               class="text-purple-600 hover:underline">
                <?php echo $submission['attachment_path']; ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Timestamps -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-800 mb-2">Timeline</h4>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Created:</span>
                <span class="font-medium"><?php echo date('M d, Y H:i:s', strtotime($submission['created_at'])); ?></span>
            </div>
            <?php if ($submission['updated_at']): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">Last Updated:</span>
                <span class="font-medium"><?php echo date('M d, Y H:i:s', strtotime($submission['updated_at'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($submission['approved_at']): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">Approved:</span>
                <span class="font-medium"><?php echo date('M d, Y H:i:s', strtotime($submission['approved_at'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($submission['rejected_at']): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">Rejected:</span>
                <span class="font-medium"><?php echo date('M d, Y H:i:s', strtotime($submission['rejected_at'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rejection Reason -->
    <?php if ($submission['rejection_reason']): ?>
    <div class="bg-red-50 border border-red-200 p-4 rounded-lg">
        <h4 class="font-semibold text-red-800 mb-2">Rejection Reason</h4>
        <div class="text-sm text-red-700"><?php echo htmlspecialchars($submission['rejection_reason']); ?></div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <?php if ($submission['status'] === 'pending'): ?>
    <div class="flex space-x-2 pt-4">
        <form method="POST" action="manage_submissions.php" class="inline">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-check mr-1"></i>Approve
            </button>
        </form>
        
        <button onclick="parent.showRejectModal(<?php echo $submission['id']; ?>); parent.hideDetailsModal();" 
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
            <i class="fas fa-times mr-1"></i>Reject
        </button>
    </div>
    <?php endif; ?>

    <!-- Debug Info (Vulnerable) -->
    <?php if (isset($_GET['debug'])): ?>
    <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
        <h4 class="font-semibold text-yellow-800 mb-2">Debug Information</h4>
        <div class="text-xs text-yellow-700">
            <p><strong>Query:</strong> <?php echo htmlspecialchars($sql); ?></p>
            <p><strong>Raw Data:</strong> <?php echo htmlspecialchars(json_encode($submission)); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>