<?php
// include/sidebar_test.php
$base_path = ''; // nếu cần điều chỉnh đường dẫn
$isLoggedIn = isset($user) && $user;
?>

<aside class="main-sidebar">
    <nav>
        <ul class="sidebar-menu">
            <li><a href="#">🏠 Trang Chủ</a></li>
            <li><a href="#">🔎 Tìm Kiếm Trọ</a></li>

            <?php if ($isLoggedIn): ?>
                <li><a href="#">👤 Hồ sơ</a></li>
                <li><a href="#">🚪 Đăng xuất</a></li>
            <?php else: ?>
                <li><a href="#">🔑 Đăng nhập</a></li>
                <li><a href="#">➕ Đăng ký</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

<!-- Nút toggle ở header cần có id="sidebarToggle" -->
<!-- Script toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggle');
    if(toggleBtn){
        toggleBtn.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
        });
    }
});
</script>
