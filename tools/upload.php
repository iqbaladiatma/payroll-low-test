<?php
session_start();
require_once './config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Vulnerable file upload - no validation (for penetration testing)
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = $file['name'];
    $tmp_name = $file['tmp_name'];
    
    // No file type validation - major security risk
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Direct file upload without sanitization
    $destination = $upload_dir . $filename;
    
    if (move_uploaded_file($tmp_name, $destination)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'File uploaded successfully',
            'filename' => $filename,
            'path' => $destination
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
    }
} else {
    // Show upload form
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>File Upload - BullsCorp</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 p-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>⚠️ WARNING:</strong> This upload form has no security validation - for testing only!
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h1 class="text-2xl font-bold mb-6">Vulnerable File Upload</h1>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select File:</label>
                        <input type="file" name="file" required class="w-full border border-gray-300 rounded-lg p-3">
                    </div>
                    
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        Upload File (Dangerous)
                    </button>
                </form>
                
                <div class="mt-6 bg-gray-50 p-4 rounded">
                    <h3 class="font-semibold mb-2">Testing Notes:</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• No file type validation</li>
                        <li>• No file size limits</li>
                        <li>• No filename sanitization</li>
                        <li>• Direct execution possible</li>
                    </ul>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>