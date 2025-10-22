<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();

$category_id = intval($_GET['id'] ?? 0);
if (!$category_id) {
    echo "<p>Không có danh mục nào được chọn.</p>";
    exit;
}

// Lấy dữ liệu danh mục
$stmt = $conn->prepare("SELECT * FROM Categories WHERE category_id=?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = trim($_POST['name'] ?? '');
    $status = intval($_POST['status'] ?? 1);

    if (!$name) $errors[] = "Tên danh mục không được để trống.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE Categories SET name=?, status=? WHERE category_id=?");
        $stmt->bind_param("sii", $name, $status, $category_id);
        if ($stmt->execute()) {
            $success_message = "🎉 Cập nhật danh mục thành công!";
            $category['name'] = $name;
            $category['status'] = $status;
        } else {
            $errors[] = "Lỗi cập nhật: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/detail_admin.css">

<div class="main-content">
    <div class="page-title"><h2>Chi tiết Danh mục</h2></div>

    <?php if(!empty($errors)): ?>
        <div class="message" style="background-color:#f8d7da;color:#721c24;border:1px solid #f5c6cb;">
            <?php foreach($errors as $err) echo "<p>⚠️ $err</p>"; ?>
        </div>
    <?php elseif($success_message): ?>
        <div class="message" style="background-color:#d1ecf1;color:#0c5460;border:1px solid #bee5eb;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="detail-wrapper">
        <div class="detail-left">
            <div class="detail-item">
                <span class="label">Tên danh mục:</span>
                <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>">
            </div>
            <div class="detail-item">
                <span class="label">Trạng thái:</span>
                <select name="status">
                    <option value="1" <?php if($category['status']==1) echo 'selected'; ?>>Hoạt động</option>
                    <option value="0" <?php if($category['status']==0) echo 'selected'; ?>>Khóa</option>
                </select>
            </div>
        </div>

        <div class="detail-actions">
            <button type="submit" name="update" class="btn-edit">Cập nhật</button>
            <button type="button" class="btn-reload" onclick="location.reload()">Load lại</button>
            <button type="button" class="btn-back" onclick="location.href='list_category.php'">Quay về danh sách</button>
        </div>
    </form>
</div>
