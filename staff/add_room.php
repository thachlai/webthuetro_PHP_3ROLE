<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/staff_header.php';
include __DIR__ . '/../include/staff_sidebar.php';
include __DIR__ . '/../include/function.php';

// Kiểm tra login & role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 2){
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// =================== Kiểm tra gói hiện tại ===================
$subscription = null;
$has_package = false;
$allow_video = false;
$allow_priority = false;

$sub_check_sql = "SELECT * FROM User_Subscriptions WHERE user_id=? AND status=1 AND is_current=1";
$stmt = $conn->prepare($sub_check_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();
$stmt->close();

if($subscription){
    $has_package = true;

    // Lấy thông tin gói
    $pkg_sql = "SELECT feature_video_allowed, is_priority_display 
                 FROM Promotion_Packages WHERE package_id=?";
    $stmt_pkg = $conn->prepare($pkg_sql);
    $stmt_pkg->bind_param("i", $subscription['package_id']);
    $stmt_pkg->execute();
    $pkg = $stmt_pkg->get_result()->fetch_assoc();
    $stmt_pkg->close();

    if($pkg){
        $allow_video = boolval($pkg['feature_video_allowed']);
        $allow_priority = boolval($pkg['is_priority_display']);
    }
}

// =================== Filter kiểu list_ward ===================
$province_filter = intval($_GET['province'] ?? ($_POST['province'] ?? 0));
$district_filter = intval($_GET['district'] ?? ($_POST['district'] ?? 0));
$ward_filter = intval($_GET['ward'] ?? ($_POST['ward'] ?? 0));

// Lấy danh sách tỉnh
$provinces = [];
$prov_res = mysqli_query($conn, "SELECT province_id, name FROM Provinces WHERE status=1 ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($prov_res)) $provinces[] = $row;

// Lấy danh sách huyện nếu tỉnh đã chọn
$districts = [];
if ($province_filter) {
    $stmt2 = mysqli_prepare($conn, "SELECT district_id, name FROM Districts WHERE province_id=? AND status=1 ORDER BY name ASC");
    mysqli_stmt_bind_param($stmt2, "i", $province_filter);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    while ($r = mysqli_fetch_assoc($res2)) $districts[] = $r;
    mysqli_stmt_close($stmt2);
}

// Lấy danh sách xã nếu huyện đã chọn
$wards = [];
if ($district_filter) {
    $stmt3 = mysqli_prepare($conn, "SELECT ward_id, name FROM Wards WHERE district_id=? AND status=1 ORDER BY name ASC");
    mysqli_stmt_bind_param($stmt3, "i", $district_filter);
    mysqli_stmt_execute($stmt3);
    $res3 = mysqli_stmt_get_result($stmt3);
    while ($r = mysqli_fetch_assoc($res3)) $wards[] = $r;
    mysqli_stmt_close($stmt3);
}

// =================== Xử lý POST đăng tin ===================
$errors = [];
$success_message = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit'])){
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? 0);
    $area = floatval($_POST['area'] ?? 0);
    $detailed_address = trim($_POST['detailed_address'] ?? '');
    $province_id = intval($_POST['province'] ?? 0);
    $district_id = intval($_POST['district'] ?? 0);
    $ward_id = intval($_POST['ward'] ?? 0);

    $is_priority_post = 0;
    if($allow_priority && isset($_POST['is_priority_post'])){
        $is_priority_post = 1;
    }

    if(!$title) $errors[] = "Tiêu đề không được để trống.";
    if(!$description) $errors[] = "Mô tả không được để trống.";
    if(!$price || $price <= 0) $errors[] = "Giá phải lớn hơn 0.";
    if(!$province_id || !$district_id || !$ward_id) $errors[] = "Vui lòng chọn đầy đủ Tỉnh/Huyện/Xã.";
    if(!$detailed_address) $errors[] = "Vui lòng nhập địa chỉ chi tiết.";

    // =================== Lưu post ===================
    if(empty($errors)){
        // Bắt đầu transaction
        mysqli_begin_transaction($conn);
        $success = false;
        try {
            $stmt = $conn->prepare("INSERT INTO posts (user_id, province_id, district_id, ward_id, title, description, price, deposit, area, detailed_address, is_priority_post, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?, 'pending', NOW())");
            $stmt->bind_param("iiissdiddsi", $user_id, $province_id, $district_id, $ward_id, $title, $description, $price, $deposit, $area, $detailed_address, $is_priority_post);
            
            if(!$stmt->execute()){
                 throw new Exception("Lỗi lưu dữ liệu Post: " . $stmt->error);
            }
            $post_id = $stmt->insert_id;
            $stmt->close();

            // =================== Upload nhiều ảnh ===================
            if(isset($_FILES['images']) && is_array($_FILES['images']) && !empty($_FILES['images']['name'][0])){
                $allowed = ['jpg','jpeg','png','gif'];
                foreach($_FILES['images']['name'] as $k => $name){
                    if($_FILES['images']['error'][$k] != UPLOAD_ERR_OK || empty($name)) continue;
                    
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if(!in_array($ext, $allowed)) continue;
                    
                    $path = 'upload/img_post/'.uniqid().'.'.$ext;
                    if(move_uploaded_file($_FILES['images']['tmp_name'][$k], __DIR__ . '/../'.$path)){
                        $stmt_img = $conn->prepare("INSERT INTO image_post (id_post, link) VALUES (?, ?)");
                        $stmt_img->bind_param("is", $post_id, $path);
                        if(!$stmt_img->execute()) throw new Exception("Lỗi lưu dữ liệu Ảnh: " . $stmt_img->error);
                        $stmt_img->close();
                    }
                }
            }

            // =================== Upload video nếu gói cho phép ===================
            if($allow_video && isset($_FILES['videos']) && is_array($_FILES['videos']) && !empty($_FILES['videos']['name'][0])){
                $allowed_vid = ['mp4','mov','avi','mkv'];
                foreach($_FILES['videos']['name'] as $k => $name){
                    if($_FILES['videos']['error'][$k] != UPLOAD_ERR_OK || empty($name)) continue;
                    
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if(!in_array($ext, $allowed_vid)) continue;
                    
                    $path = 'upload/video_post/'.uniqid().'.'.$ext;
                    if(move_uploaded_file($_FILES['videos']['tmp_name'][$k], __DIR__ . '/../'.$path)){
                        $stmt_vid = $conn->prepare("INSERT INTO video_post (id_post, link) VALUES (?, ?)");
                        $stmt_vid->bind_param("is", $post_id, $path);
                        if(!$stmt_vid->execute()) throw new Exception("Lỗi lưu dữ liệu Video: " . $stmt_vid->error);
                        $stmt_vid->close();
                    }
                }
            }

            mysqli_commit($conn);
            $success_message = "Đăng tin thành công! Tin của bạn đang chờ kiểm duyệt.";
            $success = true; // Đánh dấu thành công để reset form
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
    // Nếu thành công, reset biến POST để form hiển thị trống
    if ($success) {
        $_POST = [];
        $province_filter = 0;
        $district_filter = 0;
        $ward_filter = 0;
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/detail_admin.css">
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<style>
/* CSS cho phần thêm/xóa input */
.file-input-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
}
.file-input-group input[type="file"] {
    flex-grow: 1;
}
.remove-file-btn {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    line-height: 1;
    font-size: 1.2rem;
}
.add-file-btn {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
    display: inline-block;
}

/* CSS cho phần Preview */
.preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
    padding: 10px;
    border: 1px dashed #ccc;
    border-radius: 8px;
    background-color: #f9f9f9;
    min-height: 50px; /* Để dễ nhìn hơn khi chưa có file */
}
.file-preview-item {
    width: 120px;
    height: 120px;
    overflow: hidden;
    border-radius: 5px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: relative;
}
.preview-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Đảm bảo ảnh/video vừa khung */
    display: block;
}
</style>

<div class="main-content">
    <br> <br>
    <h2 class="page-title">Đăng tin trọ</h2>

    <?php if(!empty($errors)): ?>
        <div class="message" style="background-color:#f8d7da;color:#721c24;border:1px solid #f5c6cb;">
            <?php foreach($errors as $err) echo "<p>⚠️ $err</p>"; ?>
        </div>
    <?php elseif($success_message): ?>
        <div class="message" style="background-color:#d1ecf1;color:#0c5460;border:1px solid #bee5eb;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <div id="file-limit-message" class="message" style="display: none; background-color:#ffcc00;color:#333;border:1px solid #e0b000; margin-bottom: 15px;"></div>

    <form method="POST" enctype="multipart/form-data" class="detail-wrapper">
        <div class="detail-left">
            <div class="detail-item">
                <span class="label">Tiêu đề:</span>
                <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
            </div>

            <div class="detail-item">
                <span class="label">Mô tả:</span>
                <textarea name="description" id="description" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="detail-item">
                <span class="label">Giá (VNĐ):</span>
                <input type="number" name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" min="1" step="any" required>
            </div>

            <div class="detail-item">
                <span class="label">Tiền cọc:</span>
                <input type="number" name="deposit" value="<?php echo htmlspecialchars($_POST['deposit'] ?? ''); ?>" min="0" step="any">
            </div>

            <div class="detail-item">
                <span class="label">Diện tích (m²):</span>
                <input type="number" name="area" value="<?php echo htmlspecialchars($_POST['area'] ?? ''); ?>" min="1" step="any">
            </div>
            
            <?php if($allow_priority): ?>
            <div class="detail-item">
                <span class="label">Đăng tin ưu tiên (VIP):</span>
                <input type="checkbox" name="is_priority_post" value="1" <?php 
                    if(isset($_POST['is_priority_post'])) echo 'checked'; 
                ?>>
            </div>
            <?php endif; ?>


            <div class="detail-item">
                <span class="label">Địa chỉ chi tiết:</span>
                <input type="text" name="detailed_address" value="<?php echo htmlspecialchars($_POST['detailed_address'] ?? ''); ?>" required>
            </div>

            <!-- Tỉnh/Huyện/Xã -->
            <div class="detail-item">
                <span class="label">Tỉnh:</span>
                <select name="province" onchange="location.href='?province='+this.value;" required>
                    <option value="">Chọn tỉnh</option>
                    <?php foreach($provinces as $p): ?>
                        <option value="<?php echo $p['province_id']; ?>" <?php if($p['province_id']==$province_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="detail-item">
                <span class="label">Huyện:</span>
                <select name="district" onchange="location.href='?province=<?php echo $province_filter; ?>&district='+this.value;" required>
                    <option value="">Chọn huyện</option>
                    <?php foreach($districts as $d): ?>
                        <option value="<?php echo $d['district_id']; ?>" <?php if($d['district_id']==$district_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($d['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="detail-item">
                <span class="label">Xã:</span>
                <select name="ward" required>
                    <option value="">Chọn xã</option>
                    <?php foreach($wards as $w): ?>
                        <option value="<?php echo $w['ward_id']; ?>" <?php if($w['ward_id']==$ward_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($w['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            
            <!-- Upload ảnh -->
            <div class="detail-item">
                <span class="label">Ảnh (tối đa 10):</span>
                <div id="image-wrapper">
                    <!-- Input ảnh được tạo bằng JS -->
                </div>
                <button type="button" class="add-file-btn" onclick="addImageInput()">➕ Thêm ảnh</button>
            </div>

            <!-- Preview ảnh -->
            <div class="detail-item">
                <span class="label">Preview Ảnh:</span>
                <div id="image-preview-container" class="preview-container">
                    <!-- Vị trí hiển thị ảnh xem trước -->
                </div>
            </div>

            <?php if($allow_video): ?>
            <!-- Upload video -->
            <div class="detail-item">
                <span class="label">Video (có thể thêm nhiều):</span>
                <div id="video-wrapper">
                    <!-- Input video được tạo bằng JS -->
                </div>
                <button type="button" class="add-file-btn" onclick="addVideoInput()">➕ Thêm video</button>
            </div>

            <!-- Preview video -->
            <div class="detail-item">
                <span class="label">Preview Video:</span>
                <div id="video-preview-container" class="preview-container">
                    <!-- Vị trí hiển thị video xem trước -->
                </div>
            </div>
            <?php endif; ?>

        <div class="detail-actions">
            <button type="submit" name="submit" class="btn-edit">Đăng tin</button>
            <button type="button" class="btn-reload" onclick="location.reload()">Load lại</button>
        </div>
    </form>
</div>

<script>
ClassicEditor.create(document.querySelector('#description'));

let fileInputCounter = 0;

// Hàm hiển thị thông báo thay thế alert()
function displayFileLimitMessage(message) {
    const msgDiv = document.getElementById('file-limit-message');
    msgDiv.textContent = message;
    msgDiv.style.display = 'block';
    // Xóa thông báo sau 4 giây
    setTimeout(() => msgDiv.style.display = 'none', 4000); 
}

// Hàm xử lý khi file được chọn (tạo preview)
function handleFileChange(inputElement, type) {
    const file = inputElement.files[0];
    const uniqueId = inputElement.getAttribute('data-id');
    const previewContainerId = type === 'images' ? 'image-preview-container' : 'video-preview-container';
    const container = document.getElementById(previewContainerId);

    // Xóa preview cũ (nếu có)
    const existingPreview = document.getElementById(`preview-${uniqueId}`);
    if (existingPreview) {
        existingPreview.remove();
    }

    if (!file) return; // File cleared

    const reader = new FileReader();

    reader.onload = function(e) {
        const previewElement = document.createElement('div');
        previewElement.id = `preview-${uniqueId}`;
        previewElement.className = 'file-preview-item';

        if (type === 'images') {
            previewElement.innerHTML = `<img src="${e.target.result}" alt="Ảnh xem trước" class="preview-thumbnail">`;
        } else { // videos
            previewElement.innerHTML = `<video src="${e.target.result}" controls class="preview-thumbnail"></video>`;
        }
        
        container.appendChild(previewElement);
    };

    reader.readAsDataURL(file);
}

// Hàm tạo khung input file mới
function createFileInputGroup(name) {
    const uniqueId = `file-${fileInputCounter++}`;
    
    const group = document.createElement('div');
    group.className = 'file-input-group';
    
    const input = document.createElement('input');
    input.type = 'file';
    input.name = name + '[]';
    input.setAttribute('data-id', uniqueId); // Gán ID để link với preview
    input.accept = name === 'images' ? 'image/*' : 'video/*';
    input.onchange = (e) => handleFileChange(e.target, name);

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'remove-file-btn';
    removeBtn.textContent = '✖';
    removeBtn.onclick = function() { removeFileInput(this); };

    group.appendChild(input);
    group.appendChild(removeBtn);
    return group;
}

// Xóa khung input file VÀ preview
function removeFileInput(button) {
    const wrapper = button.closest('#image-wrapper') || button.closest('#video-wrapper');
    const group = button.parentNode;
    const inputElement = group.querySelector('input[type="file"]');
    const uniqueId = inputElement ? inputElement.getAttribute('data-id') : null;
    
    // 1. Xóa preview element
    if (uniqueId) {
        const previewElement = document.getElementById(`preview-${uniqueId}`);
        if (previewElement) {
            previewElement.remove();
        }
    }

    // 2. Xóa input field hoặc reset
    if (wrapper.children.length > 1) {
        wrapper.removeChild(group);
    } else {
        // Nếu chỉ còn 1 field, reset giá trị
        if (inputElement) inputElement.value = '';
    }
}

// Thêm input ảnh
function addImageInput() {
    let wrapper = document.getElementById('image-wrapper');
    // Giới hạn 10 input ảnh (chưa kể input đầu tiên nếu nó đã có)
    if (wrapper.children.length < 10) { 
        let newGroup = createFileInputGroup('images');
        wrapper.appendChild(newGroup);
    } else {
        displayFileLimitMessage('Bạn chỉ có thể thêm tối đa 10 ảnh.');
    }
}

// Thêm input video
function addVideoInput() {
    let wrapper = document.getElementById('video-wrapper');
    // Giới hạn 5 input video
    if (wrapper.children.length < 5) { 
        let newGroup = createFileInputGroup('videos');
        wrapper.appendChild(newGroup);
    } else {
        displayFileLimitMessage('Bạn chỉ có thể thêm tối đa 5 video.');
    }
}

// Khởi tạo ban đầu
document.addEventListener('DOMContentLoaded', function() {
    // Luôn đảm bảo có ít nhất 1 khung nhập cho Ảnh
    const imageWrapper = document.getElementById('image-wrapper');
    if (imageWrapper.children.length === 0) {
        imageWrapper.appendChild(createFileInputGroup('images'));
    }
    
    // Nếu cho phép video, đảm bảo có ít nhất 1 khung nhập cho Video
    const videoWrapper = document.getElementById('video-wrapper');
    // Kiểm tra xem videoWrapper có tồn tại không (chỉ tồn tại nếu $allow_video là true)
    if (videoWrapper && videoWrapper.children.length === 0) {
        videoWrapper.appendChild(createFileInputGroup('videos'));
    }
});
</script>
