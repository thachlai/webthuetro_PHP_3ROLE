<?php
// include/sidebar.php - (Phần HTML/PHP của Sidebar ở trên)

// Giả định biến $user đã được định nghĩa
$isLoggedIn = isset($user) && $user;
$role = $isLoggedIn ? $user['role'] : 1; 
$base_path = ''; // Cần điều chỉnh thủ công
?>

<aside class="main-sidebar">
    <nav>
        <ul class="sidebar-menu">
            
            <li><a href="<?php echo $base_path; ?>user/index.php" class="active">🏠 Trang Chủ</a></li>
            <li><a href="<?php echo $base_path; ?>user/search.php">🔎 Tìm Kiếm Trọ</a></li>
            
            <?php if ($isLoggedIn): ?>
                
                <?php if ($role == 0): // Admin ?>
                    <li><hr></li>
                    <li><a href="<?php echo $base_path; ?>admin/index.php">⚙️ Khu vực ADMIN</a></li>
                <?php elseif ($role == 2): // Chủ Trọ (Staff) ?>
                    <li><hr></li>
                    <li><a href="<?php echo $base_path; ?>staff/dashboard.php">💼 Khu vực Chủ Trọ</a></li>
                <?php endif; ?>
                
                <li><a href="<?php echo $base_path; ?>user/profile.php">👤 Cài đặt Hồ sơ</a></li>
                
            <?php endif; ?>
        </ul>
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Xử lý Nút ẩn/hiện Sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
        });
    }

    // 2. Xử lý Dropdown (Chỉ cần click thay vì hover)
    document.querySelectorAll('.dropdown-container').forEach(container => {
        const toggle = container.querySelector('.dropdown-toggle');
        const menu = container.querySelector('.dropdown-menu');

        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            // Lấy trạng thái hiện tại của dropdown
            const isMenuVisible = menu.style.display === 'block';

            // Ẩn tất cả dropdown khác trước
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                m.style.display = 'none';
            });

            // Sau đó, hiển thị dropdown hiện tại (nếu nó chưa được hiển thị)
            if (!isMenuVisible) {
                menu.style.display = 'block';
            }
        });

        // Ẩn dropdown khi click ra ngoài
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target) && !toggle.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
    });
});
</script>