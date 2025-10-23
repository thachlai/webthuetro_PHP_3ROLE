<?php
// staff_header.php - Header cho staff (người đăng trọ)

// Đảm bảo session đã start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Biến $user lấy từ session
$user = [
    'user_id' => $_SESSION['user_id'] ?? null,
    'fullname' => $_SESSION['fullname'] ?? 'Người dùng',
    'role' => $_SESSION['role'] ?? 2, // mặc định staff
    'avatar' => $_SESSION['avatar'] ?? 'upload/avatar/default.png'
];

$isLoggedIn = $user['user_id'] !== null;

// Biến base path
$base_path = '../';
?>

<header class="main-header">
<div class="header-left">
    <!-- Nút toggle sidebar -->
    <button class="sidebar-toggle-btn" id="sidebarToggle" title="Ẩn/Hiện Menu">
        &#9776;
    </button>

    <!-- Logo -->
    <a href="<?php echo $base_path; ?>staff/index.php" class="logo-link">
        <img src="<?php echo $base_path; ?>upload/logo/logo.png" alt="Logo">
    </a>
</div>


    <nav class="header-nav">
        <ul>
            <!-- Chỉ staff mới thấy Đăng tin -->
            <?php if ($isLoggedIn && $user['role'] == 2): ?>
                <li><a href="<?php echo $base_path; ?>staff/add_room.php"> ✏️ Đăng tin</a></li>
            <?php endif; ?>
                
            <!-- Bài tìm trọ -->
            <li><a href="<?php echo $base_path; ?>staff/find_posts.php">Bài tìm trọ</a></li>
            <li><a href="<?php echo $base_path; ?>staff/package.php">👑 Tham Gia Vip</a></li>   
            <!-- User icon -->
            <li class="dropdown-container">
                <a href="#" class="dropdown-toggle" id="navbarDropdownUser">
                    👤
                </a>

                <ul class="dropdown-menu" aria-labelledby="navbarDropdownUser">
                    <li class="dropdown-header">
                        <span><?php echo htmlspecialchars($user['fullname']); ?></span>
                    </li>
                    <li class="dropdown-item">
                        <img src="<?php echo $base_path . htmlspecialchars($user['avatar']); ?>" 
                             alt="Avatar" style="max-width:80px; border-radius:50%;">
                    </li>
                    <?php if($user['role'] == 0): ?>
                        <li><a href="<?php echo $base_path; ?>admin/index.php">Trang Quản trị</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $base_path; ?>user/profile.php">Hồ sơ</a></li>
                    <li><a href="<?php echo $base_path; ?>auth/logout.php">Đăng xuất</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</header>

<style>
/* CSS cơ bản cho dropdown và avatar */
.header-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.header-nav a {
    text-decoration: none;
    color: #333;
    padding: 5px 10px;
}

.dropdown-container {
    position: relative;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    background: #fff;
    border: 1px solid #ccc;
    padding: 10px;
    min-width: 150px;
    z-index: 10;
}

.dropdown-container:hover .dropdown-menu {
    display: block;
}
</style>
<script>
// Toggle sidebar
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.body.classList.toggle('sidebar-collapsed');
});
</script>