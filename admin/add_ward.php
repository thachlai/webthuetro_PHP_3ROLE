<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';

$name = '';
$province_id = '';
$district_id = '';
$status = 1;
$errors = [];
$success_message = '';

// =================== Lấy danh sách tỉnh ===================
$provinces = [];
$res = $conn->query("SELECT province_id, name FROM Provinces WHERE status=1 ORDER BY name ASC");
while ($row = $res->fetch_assoc()) $provinces[] = $row;

// =================== Xử lý form ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lấy giá trị tỉnh để load huyện
    $province_id = intval($_POST['province_id'] ?? 0);

    // =================== Lấy danh sách huyện ===================
    $districts = [];
    if ($province_id) {
        $stmt = $conn->prepare("SELECT district_id, name FROM Districts WHERE province_id=? AND status=1 ORDER BY name ASC");
        $stmt->bind_param("i", $province_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $districts[] = $row;
        $stmt->close();
    }

    // Nếu nhấn nút Thêm
    if (isset($_POST['submit'])) {
        $district_id = intval($_POST['district_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

        // Validate
        if (!$province_id) $errors[] = "Vui lòng chọn tỉnh/thành phố.";
        if (!$district_id) $errors[] = "Vui lòng chọn huyện/quận.";
        if (!$name) $errors[] = "Tên xã/phường không được để trống.";

        // Kiểm tra trùng
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT ward_id FROM Wards WHERE name=? AND district_id=?");
            $stmt->bind_param("si", $name, $district_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "Tên xã/phường đã tồn tại trong huyện này.";
            $stmt->close();
        }

        // Thêm mới
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO Wards (district_id, name, status) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $district_id, $name, $status);
            if ($stmt->execute()) {
                $success_message = "🎉 Thêm xã/phường thành công!";
                $name = '';
                $province_id = '';
                $district_id = '';
                $status = 1;
                $districts = [];
            } else $errors[] = "Lỗi khi thêm: " . $conn->error;
            $stmt->close();
        }
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/add_admin.css">

<div class="main-content">
    <div class="page-title"><h2>Thêm Xã/Phường</h2></div>

    <?php if(!empty($errors)): ?>
        <div class="message" style="background-color:#f8d7da;color:#721c24;border:1px solid #f5c6cb;">
            <?php foreach($errors as $err) echo "<p>⚠️ $err</p>"; ?>
        </div>
    <?php elseif(!empty($success_message)): ?>
        <div class="message">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <div class="add-form">
        <form method="POST" novalidate>
            <div class="form-group">
                <label>Tỉnh/Thành phố *</label>
                <select name="province_id" onchange="this.form.submit()">
                    <option value="">-- Chọn tỉnh/thành phố --</option>
                    <?php foreach($provinces as $prov): ?>
                        <option value="<?php echo $prov['province_id']; ?>" <?php if($prov['province_id']==$province_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($prov['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Huyện/Quận *</label>
                <select name="district_id">
                    <option value="">-- Chọn huyện/quận --</option>
                    <?php foreach($districts as $dist): ?>
                        <option value="<?php echo $dist['district_id']; ?>" <?php if($dist['district_id']==$district_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($dist['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tên xã/phường *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
            </div>

            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="1" <?php if($status==1) echo 'selected'; ?>>Mở</option>
                    <option value="0" <?php if($status==0) echo 'selected'; ?>>Khóa</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-add">Thêm</button>
                <button type="reset" class="btn-reset">Xóa form</button>
                <button type="button" class="btn-back" onclick="location.href='list_ward.php'">Quay về danh sách</button>
            </div>
        </form>
    </div>
</div>
