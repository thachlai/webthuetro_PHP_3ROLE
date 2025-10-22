<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();
// =================== Pagination & Filter ===================
$search = trim($_GET['search'] ?? '');
$province_filter = $_GET['province_id'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// =================== Build WHERE ===================
$where = "WHERE 1";
$params = [];
$types = '';

if($search) {
    $where .= " AND d.name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if($province_filter !== '') {
    $where .= " AND d.province_id = ?";
    $params[] = $province_filter;
    $types .= 'i';
}

if($status_filter !== '') {
    $where .= " AND d.status = ?";
    $params[] = $status_filter;
    $types .= 'i';
}

// =================== Count total ===================
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM Districts d $where");
if($types) {
    $bind_names = [];
    $bind_names[] = &$types;
    foreach($params as $key => $value) {
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
$stmt = mysqli_prepare($conn, "
    SELECT d.district_id, d.name, d.status, p.name AS province_name
    FROM Districts d
    JOIN Provinces p ON d.province_id = p.province_id
    $where
    ORDER BY d.district_id DESC
    LIMIT ?, ?
");

$params_with_limit = $params;
$types_with_limit = $types . 'ii';
$params_with_limit[] = $offset;
$params_with_limit[] = $limit;

$bind_names = [];
$bind_names[] = &$types_with_limit;
foreach($params_with_limit as $key => $value) {
    $bind_names[] = &$params_with_limit[$key];
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// =================== Provinces for filter ===================
$provinces = [];
$prov_result = mysqli_query($conn, "SELECT province_id, name FROM Provinces WHERE status=1");
while($row = mysqli_fetch_assoc($prov_result)) {
    $provinces[] = $row;
}

?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/list_admin.css">

<div class="main-content">
    <!-- 1. Filter Bar -->
    <div class="filter-bar">
        <form method="GET">
            <input type="text" name="search" placeholder="Tìm kiếm huyện..." value="<?php echo htmlspecialchars($search); ?>">

            <select name="province_id">
                <option value="">Tất cả tỉnh</option>
                <?php foreach($provinces as $prov): ?>
                    <option value="<?php echo $prov['province_id']; ?>" <?php if($province_filter==$prov['province_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($prov['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="0" <?php if($status_filter==='0') echo 'selected'; ?>>Khóa</option>
                <option value="1" <?php if($status_filter==='1') echo 'selected'; ?>>Hoạt động</option>
                <option value="2" <?php if($status_filter==='2') echo 'selected'; ?>>Đang chờ</option>
            </select>

            <button type="submit" class="btn-search">Tìm</button>
            <button type="button" class="btn-add" onclick="location.href='add_district.php'">Thêm huyện</button>
        </form>
    </div>

    <!-- 2. Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên huyện</th>
                    <th>Tỉnh</th>
                    <th>Trạng thái</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['district_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['province_name']); ?></td>
                        <td><?php echo $row['status']==0 ? 'Khóa' : ($row['status']==1 ? 'Hoạt động' : 'Đang chờ'); ?></td>
                        <td class="actions">
                            <button class="detail" onclick="location.href='detail_district.php?id=<?php echo $row['district_id']; ?>'">Chi tiết</button>
                            <button class="delete" onclick="if(confirm('Bạn có chắc muốn xóa?')) location.href='../include/delete.php?id=<?php echo $row['district_id']; ?>&table=Districts'">Xóa</button>
                            <button class="toggle" onclick="location.href='../include/status.php?id=<?php echo $row['district_id']; ?>&table=Districts&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>'">Đổi trạng thái</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- 3. Pagination -->
    <div class="pagination">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?search=<?php echo urlencode($search); ?>&province_id=<?php echo $province_filter; ?>&status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" class="<?php if($i==$page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</div>
