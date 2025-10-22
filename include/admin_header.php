<header class="main-header">
    <div class="header-left">
        <!-- Nút toggle sidebar -->
        <button class="sidebar-toggle-btn" id="sidebarToggle">☰</button>
        <!-- Logo Admin -->
        <a href="index.php" class="logo-link">Admin Panel</a>
    </div>
</header>

<script>
// Toggle sidebar
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.body.classList.toggle('sidebar-collapsed');
});
</script>
