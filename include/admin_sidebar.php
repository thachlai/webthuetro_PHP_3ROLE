<aside class="main-sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="index.php"><i class="fa fa-home"></i> Admin Page</a>
        </li>

        <li>
            <a href="#"><i class="fa fa-user"></i> Quản lý tài khoản <i class="fa fa-caret-down" style="margin-left:auto;"></i></a>
            <ul class="sidebar-submenu">
                <li><a href="list_account.php">Danh sách tài khoản</a></li>
                <li><a href="add_account.php">Thêm tài khoản</a></li>
            </ul>
        </li>

        <li>
            <a href="#"><i class="fa fa-list"></i> Quản lý Danh mục <i class="fa fa-caret-down" style="margin-left:auto;"></i></a>
            <ul class="sidebar-submenu">
                <li><a href="list_category.php">Danh sách danh mục</a></li>
                <li><a href="add_category.php">Thêm danh mục</a></li>
            </ul>
        </li>

        <li>
            <a href="#"><i class="fa fa-map-marker"></i> Quản lý Tỉnh <i class="fa fa-caret-down" style="margin-left:auto;"></i></a>
            <ul class="sidebar-submenu">
                <li><a href="list_province.php">Danh sách tỉnh</a></li>
                <li><a href="add_province.php">Thêm tỉnh</a></li>
            </ul>
        </li>

        <li>
            <a href="#"><i class="fa fa-map-signs"></i> Quản lý Huyện <i class="fa fa-caret-down" style="margin-left:auto;"></i></a>
            <ul class="sidebar-submenu">
                <li><a href="list_district.php">Danh sách huyện</a></li>
                <li><a href="add_district.php">Thêm huyện</a></li>
            </ul>
        </li>

        <li>
            <a href="#"><i class="fa fa-building"></i> Quản lý Xã <i class="fa fa-caret-down" style="margin-left:auto;"></i></a>
            <ul class="sidebar-submenu">
                <li><a href="list_ward.php">Danh sách xã</a></li>
                <li><a href="add_ward.php">Thêm xã</a></li>
            </ul>
        </li>

        <li>
            <a href="#"><i class="fa fa-gift"></i> Quản lý Gói <i class="fa fa-caret-down" style="margin-left:auto;"></i></a>
            <ul class="sidebar-submenu">
                <li><a href="list_package.php">Danh sách gói</a></li>
                <li><a href="add_package.php">Thêm gói</a></li>
            </ul>
        </li>

        <li>
            <a href="#"><i class="fa fa-shopping-cart"></i> Quản lý Gói mua <i class="fa fa-caret-down" style="margin-left:auto;"></i></a>
            <ul class="sidebar-submenu">
                <li><a href="list_usersubscription.php">Danh sách gói mua</a></li>
            </ul>
        </li>

        <li>
            <a href="#"><i class="fa fa-file-text"></i> Quản lý bài đăng <i class="fa fa-caret-down" style="margin-left:auto;"></i></a>
            <ul class="sidebar-submenu">
                <li><a href="list_post.php">Danh sách bài đăng</a></li>
            </ul>
        </li>

        <li>
            <a href="../auth/logout.php"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a>
        </li>
    </ul>
</aside>

<style>
/* Submenu mặc định ẩn */
.sidebar-submenu {
    display: none;
    list-style: none;
    padding-left: 15px;
}

/* Khi hover vào li cha, show submenu */
.sidebar-menu li:hover > .sidebar-submenu {
    display: block;
}

/* Icon caret */
.sidebar-menu li > a i.fa-caret-down {
    transition: transform 0.3s;
}

/* Xoay caret khi hover */
.sidebar-menu li:hover > a i.fa-caret-down {
    transform: rotate(90deg);
}
</style>
