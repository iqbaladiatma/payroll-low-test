<?php
$current_user = getUserById($_SESSION['user_id']);
$navbar_color = ($_SESSION['role'] == 'admin') ? 'red' : 'blue';
?>

<nav class="bg-white shadow-lg border-b-4 border-<?php echo $navbar_color; ?>-500">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center space-x-4">
                <?php if (isset($show_back_button) && $show_back_button): ?>
                <a href="<?php echo $back_url ?? '../dashboard/'; ?>" class="text-<?php echo $navbar_color; ?>-600 hover:text-<?php echo $navbar_color; ?>-800">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <?php endif; ?>
                <div class="flex items-center">
                    <i class="fas fa-bull text-<?php echo $navbar_color; ?>-600 text-2xl mr-2"></i>
                    <h1 class="text-2xl font-bold text-gray-800">
                        <?php echo $navbar_title ?? 'BullsCorp Payroll'; ?>
                    </h1>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($current_user['username']); ?></span>
                <span class="px-3 py-1 bg-<?php echo $navbar_color; ?>-100 text-<?php echo $navbar_color; ?>-800 rounded-full text-sm font-semibold">
                    <i class="fas fa-<?php echo ($_SESSION['role'] == 'admin') ? 'crown' : 'user'; ?> mr-1"></i>
                    <?php echo ucfirst($_SESSION['role']); ?>
                </span>
                <a href="/public/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</nav>