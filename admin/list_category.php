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
$limit = 50;
$offset = ($page - 1) * $limit;

// =================== Count total ===================
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

// Lấy tổng số bản ghi
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM Categories $where");
if ($types) {
    $bind_names = [];
    $bind_names[] = &$types;
    foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$total_pages = ceil($total / $limit);

// =================== Lấy dữ liệu ===================
$stmt = mysqli_prepare($conn, "SELECT category_id, name, status FROM Categories $where ORDER BY category_id DESC LIMIT ?, ?");
$params_with_limit = $params;
$types_with_limit = $types . 'ii';
$params_with_limit[] = $offset;
$params_with_limit[] = $limit;

$bind_names = [];
$bind_names[] = &$types_with_limit;
foreach ($params_with_limit as $key => $value) {
    $bind_names[] = &$params_with_limit[$key];
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
            <input type="text" name="search" placeholder="Tìm kiếm danh mục..." value="<?php echo htmlspecialchars($search); ?>">

            <!-- Lọc theo trạng thái -->
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="1" <?php if($status_filter==='1') echo 'selected'; ?>>Mở</option>
                <option value="0" <?php if($status_filter==='0') echo 'selected'; ?>>Khóa</option>
            </select>

            <button type="submit" class="btn-search">Tìm</button>
            <button type="button" class="btn-add" onclick="location.href='add_category.php'">Thêm danh mục</button>
        </form>
    </div>

    <!-- 2. Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên danh mục</th>
                    <th>Trạng thái</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['category_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo $row['status'] ? 'Mở' : 'Khóa'; ?></td>
                <td class="actions">
                    <button class="detail" onclick="location.href='detail_category.php?id=<?php echo $row['category_id']; ?>'">Chi tiết</button>
                    <button class="delete" onclick="if(confirm('Bạn có chắc muốn xóa?')) location.href='../include/delete.php?id=<?php echo $row['category_id']; ?>&table=categories'">Xóa</button>
                    <button class="toggle" onclick="location.href='../include/status.php?id=<?php echo $row['category_id']; ?>&table=categories&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>'">
                        Đổi trạng thái
                    </button>
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
