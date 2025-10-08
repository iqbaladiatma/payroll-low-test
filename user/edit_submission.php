<?php
// Edit Submission - Vulnerable for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Weak authentication check
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? $_GET['user_name'] ?? 'Test User';

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

$message = '';
$error = '';
$submission = null;

// Get submission ID from URL (vulnerable to manipulation)
$submission_id = $_GET['id'] ?? 0;

// Fetch submission details with SQL injection vulnerability
$sql = "SELECT * FROM submissions WHERE id = $submission_id";
try {
    $result = $pdo->query($sql);
    $submission = $result->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        $error = "Submission not found!";
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $error .= "<br>Query: " . htmlspecialchars($sql);
}

// Handle form submission
if ($_POST && $submission) {
    $submission_type = $_POST['submission_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $priority = $_POST['priority'] ?? 'normal';
    
    // Handle empty amount value
    $amount_value = empty($amount) ? 'NULL' : $amount;
    
    // File upload handling
    $attachment_path = $submission['attachment_path'];
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = $_FILES['attachment']['name'];
        $attachment_path = $filename;
        $full_path = $upload_dir . $filename;
        
        move_uploaded_file($_FILES['attachment']['tmp_name'], $full_path);
    }
    
    // Update submission with SQL injection vulnerability
    $update_sql = "UPDATE submissions SET 
                   submission_type = '$submission_type',
                   description = '$description',
                   start_date = '$start_date',
                   end_date = '$end_date',
                   amount = $amount_value,
                   priority = '$priority',
                   attachment_path = '$attachment_path',
                   updated_at = NOW()
                   WHERE id = $submission_id";
    
    try {
        $pdo->exec($update_sql);
        $message = "Submission updated successfully!";
        
        // Refresh submission data
        $submission = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
        $error .= "<br>Query: " . htmlspecialchars($update_sql);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Submission - BullsCorp Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-edit mr-2"></i>Edit Submission #<?php echo $submission_id; ?>
            </h1>
            <div class="flex space-x-2">
                <a href="submit_request.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Submissions
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

        <?php if ($submission): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                <i class="fas fa-edit mr-2"></i>Edit Submission Details
            </h2>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Submission Type *</label>
                        <select name="submission_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Type</option>
                            <option value="leave" <?php echo $submission['submission_type'] === 'leave' ? 'selected' : ''; ?>>Leave Request</option>
                            <option value="overtime" <?php echo $submission['submission_type'] === 'overtime' ? 'selected' : ''; ?>>Overtime Request</option>
                            <option value="expense" <?php echo $submission['submission_type'] === 'expense' ? 'selected' : ''; ?>>Expense Claim</option>
                            <option value="advance" <?php echo $submission['submission_type'] === 'advance' ? 'selected' : ''; ?>>Salary Advance</option>
                            <option value="other" <?php echo $submission['submission_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low" <?php echo $submission['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="normal" <?php echo $submission['priority'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="high" <?php echo $submission['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo $submission['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $submission['start_date']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" value="<?php echo $submission['end_date']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (if applicable)</label>
                    <input type="number" name="amount" step="0.01" min="0" value="<?php echo $submission['amount']; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Enter amount in IDR">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <textarea name="description" rows="4" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Please provide detailed description of your request..."><?php echo htmlspecialchars($submission['description']); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attachment</label>
                    <?php if ($submission['attachment_path']): ?>
                    <div class="mb-2">
                        <span class="text-sm text-gray-600">Current file: </span>
                        <a href="../uploads/<?php echo $submission['attachment_path']; ?>" target="_blank" 
                           class="text-blue-600 hover:underline">
                            <?php echo $submission['attachment_path']; ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="attachment" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">
                        Leave empty to keep current attachment
                    </p>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="submit_request.php" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        <i class="fas fa-save mr-1"></i>Update Submission
                    </button>
                </div>
            </form>
        </div>

        <!-- Submission Status -->
        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-info-circle mr-2"></i>Submission Status
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-gray-600">Status:</span>
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
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
                <div>
                    <span class="text-sm text-gray-600">Created:</span>
                    <span class="ml-2 text-sm font-medium"><?php echo date('M d, Y H:i', strtotime($submission['created_at'])); ?></span>
                </div>
                <?php if ($submission['updated_at']): ?>
                <div>
                    <span class="text-sm text-gray-600">Last Updated:</span>
                    <span class="ml-2 text-sm font-medium"><?php echo date('M d, Y H:i', strtotime($submission['updated_at'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($submission['approved_by']): ?>
                <div>
                    <span class="text-sm text-gray-600">Approved By:</span>
                    <span class="ml-2 text-sm font-medium"><?php echo $submission['approved_by']; ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($submission['rejection_reason']): ?>
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
                <span class="text-sm font-medium text-red-800">Rejection Reason:</span>
                <p class="text-sm text-red-700 mt-1"><?php echo htmlspecialchars($submission['rejection_reason']); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>