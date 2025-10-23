<?php
// staff_sidebar.php
// Sidebar đơn giản dành cho staff (role = 2)
?>

<aside class="main-sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="../staff/index.php"><i class="fa fa-home"></i> Trang Quản trị</a>
        </li>

        <li>
            <a href="../staff/add_room.php"><i class="fa fa-edit"></i> Đăng tin mới</a>
        </li>

        <li>
            <a href="../staff/list_post.php"><i class="fa fa-list"></i> Danh sách bài đăng</a>
        </li>

        <li>
            <a href="../staff/profile.php"><i class="fa fa-user"></i> Hồ sơ</a>
        </li>

        <li>
            <a href="../auth/logout.php"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a>
        </li>
    </ul>
</aside>

<style>
.main-sidebar {
    width: 200px;
    background-color: #2c3e50;
    min-height: 100vh;
    padding-top: 20px;
    position: fixed;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    margin: 0;
}

.sidebar-menu li a {
    display: block;
    padding: 12px 20px;
    color: #ecf0f1;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.3s;
}

.sidebar-menu li a:hover {
    background-color: #34495e;
}

.sidebar-menu li a i {
    margin-right: 8px;
}
</style>
