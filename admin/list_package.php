<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_login();
check_admin();
// =================== Pagination & Filter ===================
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// =================== Build WHERE ===================
$where = "WHERE 1";
$params = [];
$types = '';

if ($search) {
    $where .= " AND name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if ($status_filter !== '') {
    $where .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 'i';
}

// =================== Count total ===================
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM Promotion_Packages $where");
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

// =================== Fetch data ===================
$stmt = mysqli_prepare($conn, "SELECT package_id, name, price, duration_days, feature_video_allowed, is_priority_display, status FROM Promotion_Packages $where ORDER BY package_id DESC LIMIT ?, ?");
$params_with_limit = $params;
$types_with_limit = $types . 'ii';
$params_with_limit[] = $offset;
$params_with_limit[] = $limit;

$bind_names = [];
$bind_names[] = &$types_with_limit;
foreach ($params_with_limit as $k => $v) {
    $bind_names[] = &$params_with_limit[$k];
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/list_admin.css">

<div class="main-content">
    <!-- 1. Filter Bar -->
    <div class="filter-bar">
        <form method="GET">
            <input type="text" name="search" placeholder="Tìm kiếm gói..." value="<?php echo htmlspecialchars($search); ?>">

            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="1" <?php if($status_filter==='1') echo 'selected'; ?>>Mở</option>
                <option value="0" <?php if($status_filter==='0') echo 'selected'; ?>>Khóa</option>
            </select>

            <button type="submit" class="btn-search">Tìm</button>
            <button type="button" class="btn-add" onclick="location.href='add_package.php'">Thêm gói</button>
        </form>
    </div>

    <!-- 2. Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên gói</th>
                    <th>Giá</th>
                    <th>Ngày</th>
                    <th>Video</th>
                    <th>Ưu tiên hiển thị</th>
                    <th>Trạng thái</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['package_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo number_format($row['price'], 2); ?></td>
                    <td><?php echo $row['duration_days']; ?></td>
                    <td><?php echo $row['feature_video_allowed'] ? 'Có' : 'Không'; ?></td>
                    <td><?php echo $row['is_priority_display'] ? 'Có' : 'Không'; ?></td>
                    <td><?php echo $row['status'] ? 'Mở' : 'Khóa'; ?></td>
                    <td class="actions">
                        <button class="detail" onclick="location.href='detail_package.php?id=<?php echo $row['package_id']; ?>'">Sửa</button>
                        <button class="delete" onclick="if(confirm('Bạn có chắc muốn xóa?')) location.href='../include/delete.php?id=<?php echo $row['package_id']; ?>&table=Promotion_Packages'">Xóa</button>
                        <button class="toggle" onclick="location.href='../include/status.php?id=<?php echo $row['package_id']; ?>&table=Promotion_Packages&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>'">Đổi trạng thái</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- 3. Pagination -->
    <div class="pagination">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" class="<?php if($i==$page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</div>
