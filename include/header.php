<?php
// include/header.php - KHÔNG SỬ DỤNG BOOTSTRAP
// ----------------------------
// Giả định các biến cần thiết:
// $user: chứa thông tin user (nếu đăng nhập), NULL nếu chưa đăng nhập.
// $categories: mảng chứa danh mục (đã được query từ CSDL).
// ----------------------------

$isLoggedIn = isset($user) && $user;
$base_path = '../'; // Cần điều chỉnh nếu file ở thư mục con
?>

<header class="main-header">
    <div class="header-left">
        <button class="sidebar-toggle-btn" id="sidebarToggle" title="Ẩn/Hiện Menu">
            &#9776;
        </button>

        <a href="<?php echo $base_path; ?>user/index.php" class="logo-link">
            <img src="<?php echo $base_path; ?>upload/logo/logo.png" alt="Logo">
        </a>
    </div>

    <nav class="header-nav">
        <ul>
            <!-- Danh mục -->
            <li class="dropdown-container">
                <a href="#" class="dropdown-toggle" id="navbarDropdownCategories">
                    Danh mục
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdownCategories">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="<?php echo $base_path; ?>user/search.php?cat_id=<?php echo htmlspecialchars($cat['category_id']); ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="#">Không có danh mục</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 2): ?>
    <li><a href="../staff/add_room.php">Đăng tin</a></li>
<?php endif; ?>
            
            <li><a href="<?php echo $base_path; ?>user/posts.php">Bài đăng</a></li>
            <li><a href="<?php echo $base_path; ?>user/find_posts.php">Bài tìm trọ</a></li>

            <!-- User menu -->
            <li class="dropdown-container">
                <a href="#" class="dropdown-toggle" id="navbarDropdownUser">
                    <?php if ($isLoggedIn): ?>
                        <img src="<?php echo $base_path . htmlspecialchars($user['avatar']); ?>" 
                             alt="<?php echo htmlspecialchars($user['fullname']); ?>" 
                             class="user-avatar">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </a>

                <ul class="dropdown-menu" aria-labelledby="navbarDropdownUser">
                    <?php if ($isLoggedIn): ?>
                        <li class="dropdown-header">
                            <span><?php echo htmlspecialchars($user['fullname']); ?></span>
                        </li>
                        <li class="dropdown-divider"></li>

                        <?php if ($user['role'] == 2): ?>
                            <li><a href="<?php echo $base_path; ?>staff/add_post.php">🏠 Thêm Bài Đăng Trọ</a></li>
                        <?php elseif ($user['role'] == 1): ?>
                            <li><a href="<?php echo $base_path; ?>user/add_find_post.php">🔎 Thêm Bài Tìm Trọ</a></li>
                        <?php endif; ?>

                        <li><a href="<?php echo $base_path; ?>user/profile.php">👤 Hồ sơ</a></li>
                        <li class="dropdown-divider"></li>
                        <li><a href="<?php echo $base_path; ?>auth/logout.php">🚪 Đăng xuất</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_path; ?>auth/login.php">🔑 Đăng nhập</a></li>
                        <li><a href="<?php echo $base_path; ?>auth/signup.php">➕ Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        </ul>
    </nav>
</header>
