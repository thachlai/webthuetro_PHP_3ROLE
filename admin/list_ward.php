<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();
// =================== Filter ===================
$search = trim($_GET['search'] ?? '');
$province_filter = $_GET['province'] ?? '';
$district_filter = $_GET['district'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// =================== Count total ===================
$where = "WHERE 1";
$params = [];
$types = '';

if ($search) {
    $where .= " AND w.name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if ($province_filter !== '') {
    $where .= " AND d.province_id = ?";
    $params[] = $province_filter;
    $types .= 'i';
}

if ($district_filter !== '') {
    $where .= " AND w.district_id = ?";
    $params[] = $district_filter;
    $types .= 'i';
}

if ($status_filter !== '') {
    $where .= " AND w.status = ?";
    $params[] = $status_filter;
    $types .= 'i';
}

// Count total
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM Wards w JOIN Districts d ON w.district_id=d.district_id $where");
if ($types) {
    $bind_names = [];
    $bind_names[] = &$types;
    foreach ($params as $k => $v) $bind_names[] = &$params[$k];
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$total_pages = ceil($total / $limit);

// =================== Lấy dữ liệu ===================
$stmt = mysqli_prepare($conn, "
    SELECT w.ward_id, w.name AS ward_name, w.status, d.name AS district_name, d.province_id
    FROM Wards w
    JOIN Districts d ON w.district_id=d.district_id
    $where
    ORDER BY w.ward_id DESC
    LIMIT ?, ?
");
$params_with_limit = $params;
$types_with_limit = $types . 'ii';
$params_with_limit[] = $offset;
$params_with_limit[] = $limit;

$bind_names = [];
$bind_names[] = &$types_with_limit;
foreach ($params_with_limit as $k => $v) $bind_names[] = &$params_with_limit[$k];
call_user_func_array([$stmt, 'bind_param'], $bind_names);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// =================== Lấy danh sách tỉnh ===================
$provinces = [];
$prov_res = mysqli_query($conn, "SELECT province_id, name FROM Provinces WHERE status=1");
while ($row = mysqli_fetch_assoc($prov_res)) $provinces[] = $row;

// =================== Lấy danh sách huyện nếu tỉnh đã chọn ===================
$districts = [];
if ($province_filter) {
    $stmt2 = mysqli_prepare($conn, "SELECT district_id, name FROM Districts WHERE province_id=? AND status=1");
    mysqli_stmt_bind_param($stmt2, "i", $province_filter);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    while ($r = mysqli_fetch_assoc($res2)) $districts[] = $r;
    mysqli_stmt_close($stmt2);
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/list_admin.css">

<div class="main-content">
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET">
            <input type="text" name="search" placeholder="Tìm kiếm xã/phường..." value="<?php echo htmlspecialchars($search); ?>">

            <select name="province" id="province-select" onchange="this.form.submit()">
                <option value="">Chọn Tỉnh</option>
                <?php foreach ($provinces as $p): ?>
                    <option value="<?php echo $p['province_id']; ?>" <?php if($province_filter==$p['province_id']) echo 'selected'; ?>><?php echo htmlspecialchars($p['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="district" id="district-select">
                <option value="">Chọn Huyện</option>
                <?php foreach ($districts as $d): ?>
                    <option value="<?php echo $d['district_id']; ?>" <?php if($district_filter==$d['district_id']) echo 'selected'; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="1" <?php if($status_filter==='1') echo 'selected'; ?>>Hoạt động</option>
                <option value="0" <?php if($status_filter==='0') echo 'selected'; ?>>Khóa</option>
            </select>

            <button type="submit" class="btn-search">Tìm</button>
            <button type="button" class="btn-add" onclick="location.href='add_ward.php'">Thêm xã/phường</button>
        </form>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên xã/phường</th>
                    <th>Huyện</th>
                    <th>Trạng thái</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['ward_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['ward_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['district_name']); ?></td>
                    <td><?php echo $row['status'] ? 'Hoạt động' : 'Khóa'; ?></td>
                    <td class="actions">
                        <button class="detail" onclick="location.href='detail_ward.php?id=<?php echo $row['ward_id']; ?>'">Chi tiết</button>
                        <button class="delete" onclick="if(confirm('Bạn có chắc muốn xóa?')) location.href='../include/delete.php?id=<?php echo $row['ward_id']; ?>&table=Wards'">Xóa</button>
                        <button class="toggle" onclick="location.href='../include/status.php?id=<?php echo $row['ward_id']; ?>&table=Wards&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>'">Đổi trạng thái</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?search=<?php echo urlencode($search); ?>&province=<?php echo $province_filter; ?>&district=<?php echo $district_filter; ?>&status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" class="<?php if($i==$page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</div>
