<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();

// =================== Pagination & Filter ===================
$search = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$province_filter = $_GET['province'] ?? '';
$district_filter = $_GET['district'] ?? '';
$ward_filter = $_GET['ward'] ?? '';
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$area_min = $_GET['area_min'] ?? '';
$area_max = $_GET['area_max'] ?? '';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// =================== Build WHERE ===================
$where = "WHERE 1";
$params = [];
$types = '';

if ($search) {
    $where .= " AND title LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
if ($category_filter) {
    $where .= " AND category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}
if ($province_filter) {
    $where .= " AND province_id = ?";
    $params[] = $province_filter;
    $types .= 'i';
}
if ($district_filter) {
    $where .= " AND district_id = ?";
    $params[] = $district_filter;
    $types .= 'i';
}
if ($ward_filter) {
    $where .= " AND ward_id = ?";
    $params[] = $ward_filter;
    $types .= 'i';
}
if ($status_filter) {
    $where .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($priority_filter !== '') {
    $where .= " AND is_priority_post = ?";
    $params[] = $priority_filter;
    $types .= 'i';
}
if ($area_min !== '') {
    $where .= " AND area >= ?";
    $params[] = $area_min;
    $types .= 'd';
}
if ($area_max !== '') {
    $where .= " AND area <= ?";
    $params[] = $area_max;
    $types .= 'd';
}

// =================== Count total ===================
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM posts $where");
if ($types) {
    $bind_names = [];
    $bind_names[] = &$types;
    foreach ($params as $key => $value) $bind_names[] = &$params[$key];
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$total_pages = ceil($total / $limit);

// =================== Fetch data ===================
$query = "SELECT p.id_post, u.fullname, p.title, p.description, c.name as category_name,
          pr.name as province_name, d.name as district_name, w.name as ward_name,
          p.price, p.area, p.is_priority_post, p.status
          FROM posts p
          LEFT JOIN users u ON p.user_id = u.user_id
          LEFT JOIN Categories c ON p.category_id = c.category_id
          LEFT JOIN Provinces pr ON p.province_id = pr.province_id
          LEFT JOIN Districts d ON p.district_id = d.district_id
          LEFT JOIN Wards w ON p.ward_id = w.ward_id
          $where
          ORDER BY p.id_post DESC
          LIMIT ?, ?";

$params_with_limit = $params;
$types_with_limit = $types . 'ii';
$params_with_limit[] = $offset;
$params_with_limit[] = $limit;

$stmt = mysqli_prepare($conn, $query);
$bind_names = [];
$bind_names[] = &$types_with_limit;
foreach ($params_with_limit as $key => $value) $bind_names[] = &$params_with_limit[$key];
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
            <input type="text" name="search" placeholder="Tìm theo tên bài đăng..." value="<?php echo htmlspecialchars($search); ?>">

            <select name="category">
                <option value="">Tất cả danh mục</option>
                <?php
                $cat_res = mysqli_query($conn, "SELECT category_id, name FROM Categories WHERE status=1");
                while ($cat = mysqli_fetch_assoc($cat_res)) {
                    $sel = $category_filter==$cat['category_id']?'selected':'';
                    echo "<option value='{$cat['category_id']}' $sel>{$cat['name']}</option>";
                }
                ?>
            </select>

            <select name="province" id="province-select">
                <option value="">Tỉnh/Thành</option>
                <?php
                $prov_res = mysqli_query($conn, "SELECT province_id, name FROM Provinces WHERE status=1");
                while ($prov = mysqli_fetch_assoc($prov_res)) {
                    $sel = $province_filter==$prov['province_id']?'selected':'';
                    echo "<option value='{$prov['province_id']}' $sel>{$prov['name']}</option>";
                }
                ?>
            </select>

            <select name="district" id="district-select">
                <option value="">Quận/Huyện</option>
            </select>

            <select name="ward" id="ward-select">
                <option value="">Xã/Phường</option>
            </select>

            <input type="number" step="0.01" name="area_min" placeholder="Diện tích min" value="<?php echo $area_min; ?>">
            <input type="number" step="0.01" name="area_max" placeholder="Diện tích max" value="<?php echo $area_max; ?>">

            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" <?php if($status_filter=='pending') echo 'selected'; ?>>Pending</option>
                <option value="active" <?php if($status_filter=='active') echo 'selected'; ?>>Active</option>
                <option value="rented" <?php if($status_filter=='rented') echo 'selected'; ?>>Rented</option>
                <option value="expired" <?php if($status_filter=='expired') echo 'selected'; ?>>Expired</option>
            </select>

            <select name="priority">
                <option value="">Tất cả ưu tiên</option>
                <option value="1" <?php if($priority_filter==='1') echo 'selected'; ?>>Ưu tiên</option>
                <option value="0" <?php if($priority_filter==='0') echo 'selected'; ?>>Không ưu tiên</option>
            </select>

            <button type="submit" class="btn-search">Tìm</button>
            <button type="button" class="btn-add" onclick="location.href='add_post.php'">Thêm bài đăng</button>
        </form>
    </div>

    <!-- 2. Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Người đăng</th>
                    <th>Tên bài đăng</th>
                    <th>Mô tả nhỏ</th>
                    <th>Danh mục</th>
                    <th>Tỉnh/Quận/Xã</th>
                    <th>Giá</th>
                    <th>Diện tích</th>
                    <th>Ưu tiên</th>
                    <th>Trạng thái</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['id_post']; ?></td>
                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 50, '...')); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['province_name'].'/'.$row['district_name'].'/'.$row['ward_name']); ?></td>
                    <td><?php echo number_format($row['price']); ?></td>
                    <td><?php echo $row['area']; ?></td>
                    <td><?php echo $row['is_priority_post'] ? 'Có' : 'Không'; ?></td>
                    <td><?php echo ucfirst($row['status']); ?></td>
                    <td class="actions">
                        <button class="detail" onclick="location.href='detail_post.php?id=<?php echo $row['id_post']; ?>'">Chi tiết</button>
                        <button class="delete" onclick="if(confirm('Bạn có chắc muốn xóa?')) location.href='../include/delete.php?id=<?php echo $row['id_post']; ?>&table=posts'">Xóa</button>
                        <button class="toggle" onclick="location.href='../include/status.php?id=<?php echo $row['id_post']; ?>&table=posts&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>'">
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
            <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$i])); ?>" class="<?php if($i==$page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</div>

<!-- JS load District & Ward động -->
<script>
document.addEventListener('DOMContentLoaded', function(){
    const provinceSelect = document.getElementById('province-select');
    const districtSelect = document.getElementById('district-select');
    const wardSelect = document.getElementById('ward-select');

    function loadDistricts(provinceId, selectedDistrict = '') {
        districtSelect.innerHTML = '<option value="">Quận/Huyện</option>';
        wardSelect.innerHTML = '<option value="">Xã/Phường</option>';
        if(!provinceId) return;
        fetch('../include/get_districts.php?province_id=' + provinceId)
            .then(res => res.json())
            .then(data => {
                data.forEach(d=>{
                    const sel = d.district_id == selectedDistrict ? 'selected' : '';
                    districtSelect.innerHTML += `<option value="${d.district_id}" ${sel}>${d.name}</option>`;
                });
            });
    }

    function loadWards(districtId, selectedWard = '') {
        wardSelect.innerHTML = '<option value="">Xã/Phường</option>';
        if(!districtId) return;
        fetch('../include/get_wards.php?district_id=' + districtId)
            .then(res => res.json())
            .then(data => {
                data.forEach(w=>{
                    const sel = w.ward_id == selectedWard ? 'selected' : '';
                    wardSelect.innerHTML += `<option value="${w.ward_id}" ${sel}>${w.name}</option>`;
                });
            });
    }

    provinceSelect.addEventListener('change',()=> loadDistricts(provinceSelect.value));
    districtSelect.addEventListener('change',()=> loadWards(districtSelect.value));

    <?php if($province_filter) echo "loadDistricts($province_filter, '$district_filter');"; ?>
    <?php if($district_filter) echo "loadWards($district_filter, '$ward_filter');"; ?>
});
</script>
