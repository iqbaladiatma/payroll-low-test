<?php
// Admin Sidebar Component - Reusable across all admin pages
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'admin';
$username = $_SESSION['username'] ?? 'admin';

// Get pending submissions count for badge
try {
    $pending_submissions = $pdo->query("SELECT COUNT(*) FROM submissions WHERE status = 'pending'")->fetchColumn();
} catch (Exception $e) {
    $pending_submissions = 0;
}
?>

<!-- Sidebar -->
<div id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-gradient-to-b from-gray-800 to-gray-900 text-white shadow-2xl transform transition-transform duration-300 ease-in-out z-50">
    <!-- Sidebar Header -->
    <div class="p-6 border-b border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-building text-white text-xl"></i>
                </div>
                <div class="sidebar-text">
                    <h1 class="text-xl font-bold">BullsCorp</h1>
                    <p class="text-sm text-gray-300">Admin Panel</p>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="text-gray-300 hover:text-white transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="p-4 space-y-2 flex-1 overflow-y-auto">
        <a href="dashboard_modern.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 <?php echo $current_page === 'dashboard_modern.php' ? 'bg-blue-600 text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-tachometer-alt w-5 text-center"></i>
            <span class="sidebar-text">Dashboard</span>
        </a>
        
        <a href="manage_submissions.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 <?php echo $current_page === 'manage_submissions.php' ? 'bg-blue-600 text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-clipboard-list w-5 text-center"></i>
            <span class="sidebar-text">Submissions</span>
            <?php if ($pending_submissions > 0): ?>
            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-auto sidebar-text">
                <?php echo $pending_submissions; ?>
            </span>
            <?php endif; ?>
        </a>
        
        <a href="manage_employees.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 <?php echo $current_page === 'manage_employees.php' ? 'bg-blue-600 text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-users w-5 text-center"></i>
            <span class="sidebar-text">Employees</span>
        </a>
        
        <a href="manage_payroll.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 <?php echo $current_page === 'manage_payroll.php' ? 'bg-blue-600 text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-money-check-alt w-5 text-center"></i>
            <span class="sidebar-text">Payroll</span>
        </a>
        
        <a href="attendance_report.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 <?php echo $current_page === 'attendance_report.php' ? 'bg-blue-600 text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-calendar-check w-5 text-center"></i>
            <span class="sidebar-text">Attendance</span>
        </a>
        
        <a href="reports.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 <?php echo $current_page === 'reports.php' ? 'bg-blue-600 text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-chart-line w-5 text-center"></i>
            <span class="sidebar-text">Reports</span>
        </a>
        
        <a href="system_logs.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 <?php echo $current_page === 'system_logs.php' ? 'bg-blue-600 text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-list-alt w-5 text-center"></i>
            <span class="sidebar-text">System Logs</span>
        </a>
        
        <div class="border-t border-gray-700 my-4"></div>
        
        <a href="add_employee_page.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 <?php echo $current_page === 'add_employee.php' ? 'bg-blue-600 text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-user-plus w-5 text-center"></i>
            <span class="sidebar-text">Add Employee</span>
        </a>
        
        <a href="../debug.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 hover:bg-gray-700">
            <i class="fas fa-bug w-5 text-center"></i>
            <span class="sidebar-text">Debug Info</span>
        </a>
    </nav>

    <!-- User Info & Logout -->
    <div class="p-4 border-t border-gray-700">
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-sm"></i>
            </div>
            <div class="sidebar-text">
                <p class="font-medium text-sm"><?php echo htmlspecialchars($username); ?></p>
                <p class="text-xs text-gray-300"><?php echo ucfirst($user_role); ?></p>
            </div>
        </div>
        <a href="../public/logout.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg hover:bg-red-600 transition-all duration-200">
            <i class="fas fa-sign-out-alt w-5 text-center"></i>
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</div>

<!-- Sidebar Toggle Button (when minimized) -->
<button id="sidebarToggle" class="fixed left-4 top-4 z-40 bg-gray-800 text-white p-3 rounded-lg shadow-lg hover:bg-gray-700 transition-all duration-200 hidden">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay for mobile -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

<style>
/* Sidebar animations and responsive behavior */
.sidebar-minimized {
    width: 4rem !important;
}

.sidebar-minimized .sidebar-text {
    display: none;
}

.sidebar-minimized .sidebar-item {
    justify-content: center;
}

@media (max-width: 1024px) {
    #sidebar {
        transform: translateX(-100%);
    }
    
    #sidebar.show {
        transform: translateX(0);
    }
    
    #sidebarToggle {
        display: block !important;
    }
}

/* Smooth transitions */
#sidebar {
    transition: all 0.3s ease-in-out;
}

.sidebar-item:hover {
    transform: translateX(2px);
}
</style>

<script>
let sidebarMinimized = false;
let sidebarVisible = window.innerWidth >= 1024;

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (window.innerWidth >= 1024) {
        // Desktop: minimize/expand
        sidebarMinimized = !sidebarMinimized;
        sidebar.classList.toggle('sidebar-minimized', sidebarMinimized);
        toggle.classList.toggle('hidden', !sidebarMinimized);
        
        // Adjust main content margin
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.style.marginLeft = sidebarMinimized ? '4rem' : '16rem';
        }
    } else {
        // Mobile: show/hide
        sidebarVisible = !sidebarVisible;
        sidebar.classList.toggle('show', sidebarVisible);
        overlay.classList.toggle('hidden', !sidebarVisible);
        toggle.classList.toggle('hidden', sidebarVisible);
    }
}

// Close sidebar when clicking overlay
document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (window.innerWidth >= 1024) {
        sidebar.classList.remove('show');
        overlay.classList.add('hidden');
        toggle.classList.toggle('hidden', !sidebarMinimized);
    } else {
        sidebar.classList.remove('sidebar-minimized');
        toggle.classList.toggle('hidden', sidebarVisible);
        sidebarMinimized = false;
    }
});

// Initialize sidebar state
document.addEventListener('DOMContentLoaded', function() {
    const mainContent = document.getElementById('mainContent');
    if (mainContent && window.innerWidth >= 1024) {
        mainContent.style.marginLeft = '16rem';
    }
});
</script>