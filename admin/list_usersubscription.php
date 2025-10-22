<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();

// =================== Pagination & Filter ===================
$search_name = trim($_GET['search_name'] ?? '');
$status_filter = $_GET['status'] ?? '';
$role_filter = $_GET['role'] ?? '';
$package_filter = $_GET['package_id'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// =================== Build WHERE ===================
$where = "WHERE 1";
$params = [];
$types = '';

// Tìm kiếm theo tên người dùng
if ($search_name) {
    $where .= " AND u.fullname LIKE ?";
    $params[] = "%$search_name%";
    $types .= 's';
}

// Lọc theo trạng thái subscription
if ($status_filter !== '') {
    $where .= " AND us.status = ?";
    $params[] = $status_filter;
    $types .= 'i';
}

// Lọc theo vai trò người dùng
if ($role_filter !== '') {
    $where .= " AND u.role = ?";
    $params[] = $role_filter;
    $types .= 'i';
}

// Lọc theo gói
if ($package_filter !== '') {
    $where .= " AND us.package_id = ?";
    $params[] = $package_filter;
    $types .= 'i';
}

// =================== Count total ===================
$sql_count = "SELECT COUNT(*) FROM User_Subscriptions us 
              INNER JOIN users u ON us.user_id = u.user_id
              INNER JOIN Promotion_Packages p ON us.package_id = p.package_id
              $where";
$stmt = mysqli_prepare($conn, $sql_count);
if ($types) {
    $bind_names = [];
    $bind_names[] = &$types;
    foreach ($params as $k => $v) {
        $bind_names[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$total_pages = ceil($total / $limit);

// =================== Lấy dữ liệu ===================
$sql = "SELECT us.subscription_id, u.fullname, u.role, us.status, us.start_time, us.end_time, p.name AS package_name
        FROM User_Subscriptions us
        INNER JOIN users u ON us.user_id = u.user_id
        INNER JOIN Promotion_Packages p ON us.package_id = p.package_id
        $where
        ORDER BY us.subscription_id DESC
        LIMIT ?, ?";
$params_with_limit = $params;
$types_with_limit = $types . 'ii';
$params_with_limit[] = $offset;
$params_with_limit[] = $limit;

$stmt = mysqli_prepare($conn, $sql);
$bind_names = [];
$bind_names[] = &$types_with_limit;
foreach ($params_with_limit as $k => $v) {
    $bind_names[] = &$params_with_limit[$k];
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Lấy danh sách gói để dropdown
$package_list = [];
$res_pkg = mysqli_query($conn, "SELECT package_id, name FROM Promotion_Packages WHERE is_active=1");
while($row_pkg = mysqli_fetch_assoc($res_pkg)){
    $package_list[] = $row_pkg;
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/list_admin.css">

<div class="main-content">
    <!-- 1. Filter Bar -->
    <div class="filter-bar">
        <form method="GET">
            <input type="text" name="search_name" placeholder="Tìm theo tên người dùng..." value="<?php echo htmlspecialchars($search_name); ?>">

            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="1" <?php if($status_filter==='1') echo 'selected'; ?>>Hiệu lực</option>
                <option value="0" <?php if($status_filter==='0') echo 'selected'; ?>>Hết hạn</option>
            </select>

            <select name="role">
                <option value="">Tất cả vai trò</option>
                <option value="0" <?php if($role_filter==='0') echo 'selected'; ?>>Admin</option>
                <option value="1" <?php if($role_filter==='1') echo 'selected'; ?>>Người dùng</option>
                <option value="2" <?php if($role_filter==='2') echo 'selected'; ?>>Chủ trọ</option>
            </select>

            <select name="package_id">
                <option value="">Tất cả gói</option>
                <?php foreach($package_list as $pkg): ?>
                    <option value="<?php echo $pkg['package_id']; ?>" <?php if($package_filter==$pkg['package_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($pkg['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-search">Lọc</button>
        </form>
    </div>

    <!-- 2. Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Người dùng</th>
                    <th>Vai trò</th>
                    <th>Gói</th>
                    <th>Trạng thái</th>
                    <th>Bắt đầu</th>
                    <th>Kết thúc</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['subscription_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                    <td>
                        <?php
                        switch($row['role']){
                            case 0: echo 'Admin'; break;
                            case 1: echo 'Người dùng'; break;
                            case 2: echo 'Chủ trọ'; break;
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['package_name']); ?></td>
                    <td><?php echo $row['status'] ? 'Hiệu lực' : 'Hết hạn'; ?></td>
                    <td><?php echo $row['start_time']; ?></td>
                    <td><?php echo $row['end_time']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- 3. Pagination -->
    <div class="pagination">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?search_name=<?php echo urlencode($search_name); ?>&status=<?php echo $status_filter; ?>&role=<?php echo $role_filter; ?>&package_id=<?php echo $package_filter; ?>&page=<?php echo $i; ?>" class="<?php if($i==$page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</div>
