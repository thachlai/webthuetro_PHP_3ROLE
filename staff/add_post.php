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

// =================== Lấy danh sách Danh mục đang hoạt động ===================
$categories = [];
$cat_res = mysqli_query($conn, "SELECT category_id, name FROM Categories WHERE status=1 ORDER BY name ASC");
if($cat_res) {
    while ($row = mysqli_fetch_assoc($cat_res)) $categories[] = $row;
}

// =================== Filter kiểu list_ward (Giữ giá trị cũ nếu có) ===================
// Lấy giá trị đã chọn sau khi POST hoặc từ query string
$province_filter = intval($_GET['province'] ?? ($_POST['province'] ?? 0));
$district_filter = intval($_GET['district'] ?? ($_POST['district'] ?? 0));
$ward_filter = intval($_GET['ward'] ?? ($_POST['ward'] ?? 0));
$selected_category_id = intval($_POST['category_id'] ?? 0); 

// Lấy danh sách tỉnh (Dùng cho dropdown đầu tiên)
$provinces = [];
$prov_res = mysqli_query($conn, "SELECT province_id, name FROM Provinces WHERE status=1 ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($prov_res)) $provinces[] = $row;

// Lấy danh sách huyện nếu tỉnh đã chọn (Chỉ dùng cho pre-population khi lỗi POST/reload)
$districts = [];
if ($province_filter) {
    $stmt2 = mysqli_prepare($conn, "SELECT district_id, name FROM Districts WHERE province_id=? AND status=1 ORDER BY name ASC");
    mysqli_stmt_bind_param($stmt2, "i", $province_filter);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    while ($r = mysqli_fetch_assoc($res2)) $districts[] = $r;
    mysqli_stmt_close($stmt2);
}

// Lấy danh sách xã nếu huyện đã chọn (Chỉ dùng cho pre-population khi lỗi POST/reload)
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
    $category_id = intval($_POST['category_id'] ?? 0);

    $is_priority_post = 0;
    if($allow_priority && isset($_POST['is_priority_post'])){
        $is_priority_post = 1;
    }

    if(!$title) $errors[] = "Tiêu đề không được để trống.";
    if(!$description) $errors[] = "Mô tả không được để trống.";
    if(!$price || $price <= 0) $errors[] = "Giá phải lớn hơn 0.";
    if(!$province_id || !$district_id || !$ward_id) $errors[] = "Vui lòng chọn đầy đủ Tỉnh/Huyện/Xã.";
    if(!$detailed_address) $errors[] = "Vui lòng nhập địa chỉ chi tiết.";
    if(!$category_id) $errors[] = "Vui lòng chọn Danh mục cho bài đăng.";

    // =================== Lưu post ===================
    if(empty($errors)){
        // Bắt đầu transaction
        mysqli_begin_transaction($conn);
        $success = false;
        try {
            // CẬP NHẬT: Thêm category_id vào INSERT statement
            $stmt = $conn->prepare("INSERT INTO posts (user_id, category_id, province_id, district_id, ward_id, title, description, price, deposit, area, detailed_address, is_priority_post, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'pending', NOW())");
            // CẬP NHẬT: Thêm category_id (i) vào bind_param. Chuỗi types mới: "iiiissdiddsi"
            $stmt->bind_param("iiiissdiddsi", $user_id, $category_id, $province_id, $district_id, $ward_id, $title, $description, $price, $deposit, $area, $detailed_address, $is_priority_post);
            
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
        $selected_category_id = 0;
    } else {
        // Nếu thất bại, giữ lại các giá trị đã chọn
        $selected_category_id = intval($_POST['category_id'] ?? 0);
        // Cần cập nhật lại filter nếu có lỗi POST
        $province_filter = intval($_POST['province'] ?? 0);
        $district_filter = intval($_POST['district'] ?? 0);
        $ward_filter = intval($_POST['ward'] ?? 0);
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/detail_admin.css">
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<style>
/* CSS cho phần thêm/xóa input (để lại vì nó cần cho HTML) */
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
            
            <!-- KHU VỰC PREVIEW FILE TỔNG HỢP -->
            <div class="detail-item" style="flex-direction: column; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 1px dashed #ddd;">
                <span class="label" style="width: auto; margin-bottom: 10px; padding-left: 0;">Danh sách Ảnh/Video đã chọn:</span>
                <div id="combined-preview-container" class="file-preview-container">
                    <!-- Vị trí hiển thị ảnh/video xem trước (Ảnh 1++, Video 1++,...) -->
                    <span style="color: #999; font-style: italic;">Chưa có file nào được chọn.</span>
                </div>
            </div>
            <!-- END NEW PREVIEW SECTION -->


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
            
            <!-- TRƯỜNG DANH MỤC -->
            <div class="detail-item">
                <span class="label">Danh mục:</span>
                <select name="category_id" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" 
                            <?php if($cat['category_id'] == $selected_category_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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

            <!-- Tỉnh/Huyện/Xã: SỬ DỤNG AJAX -->
            <div class="detail-item">
                <span class="label">Tỉnh:</span>
                <select name="province" id="province-select" onchange="fetchDistricts(this.value)" required>
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
                <select name="district" id="district-select" onchange="fetchWards(this.value)" required>
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
                <select name="ward" id="ward-select" required>
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
                <div style="flex: 1;">
                    <div id="image-wrapper">
                        <!-- Input ảnh được tạo bằng JS -->
                    </div>
                    <!-- Các hàm JS addImageInput/addVideoInput/removeFileInput phải được định nghĩa trong file JS mới -->
                    <button type="button" class="add-file-btn" onclick="addImageInput()">➕ Thêm ảnh</button>
                </div>
            </div>

            <?php if($allow_video): ?>
            <!-- Upload video -->
            <div class="detail-item">
                <span class="label">Video (tối đa 5):</span>
                <div style="flex: 1;">
                    <div id="video-wrapper">
                        <!-- Input video được tạo bằng JS -->
                    </div>
                    <button type="button" class="add-file-btn" onclick="addVideoInput()">➕ Thêm video</button>
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

// ======================= LOGIC AJAX (GIỮ LẠI TRONG PHP VÌ NÓ THAY ĐỔI THEO DATA PHP) =======================

// Hàm reset một dropdown
function resetDropdown(elementId, defaultText, isEnabled = true) {
    const select = document.getElementById(elementId);
    select.innerHTML = '';
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = defaultText;
    select.appendChild(defaultOption);
    select.disabled = !isEnabled;
}

/**
 * Gửi yêu cầu AJAX để lấy danh sách địa điểm (Huyện hoặc Xã)
 */
function fetchLocations(parentId, type, childSelectId, nextSelectId) {
    // Địa chỉ của file PHP xử lý AJAX (CẦN TẠO FILE NÀY TRÊN SERVER)
    const apiUrl = '../include/fetch_locations_ajax.php'; 
    
    // Reset dropdown con và dropdown cháu
    resetDropdown(childSelectId, `Đang tải ${type}...`, false);
    if (nextSelectId) {
        if (nextSelectId === 'ward-select') {
            resetDropdown(nextSelectId, 'Chọn xã');
        } else if (nextSelectId === 'district-select') {
            resetDropdown(nextSelectId, 'Chọn huyện');
        }
    }

    if (!parentId || parentId == 0) {
        resetDropdown(childSelectId, `Chọn ${type}`);
        return;
    }

    // Gửi request AJAX
    // type: 'district' hoặc 'ward'
    const requestType = type === 'Huyện' ? 'district' : 'ward'; 

    fetch(apiUrl + `?parent_id=${parentId}&type=${requestType}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Mạng hoặc server response bị lỗi.');
            }
            return response.json();
        })
        .then(data => {
            const childSelect = document.getElementById(childSelectId);
            childSelect.disabled = false;
            resetDropdown(childSelectId, `Chọn ${type}`); // Reset lại option "Chọn..."

            if (data && data.length > 0) {
                data.forEach(item => {
                    const option = document.createElement('option');
                    // Tùy thuộc vào 'type', sử dụng đúng ID
                    if (type === 'Huyện') {
                        option.value = item.district_id;
                    } else if (type === 'Xã') {
                        option.value = item.ward_id;
                    }
                    option.textContent = item.name;
                    childSelect.appendChild(option);
                });
            } else {
                // Sử dụng hàm báo lỗi (từ file JS mới)
                if (typeof displayFileLimitMessage === 'function') {
                    displayFileLimitMessage(`Không tìm thấy ${type} đang hoạt động trong khu vực này.`);
                }
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải dữ liệu địa điểm:', error);
            document.getElementById(childSelectId).disabled = false;
            resetDropdown(childSelectId, `Lỗi tải ${type}`);
            // Sử dụng hàm báo lỗi (từ file JS mới)
            if (typeof displayFileLimitMessage === 'function') {
                 displayFileLimitMessage(`Lỗi kết nối khi tải ${type}. Vui lòng kiểm tra file AJAX.`);
            }
        });
}

function fetchDistricts(provinceId) {
    fetchLocations(provinceId, 'Huyện', 'district-select', 'ward-select');
}

function fetchWards(districtId) {
    fetchLocations(districtId, 'Xã', 'ward-select', null);
}

// Khởi tạo lại dropdown nếu có giá trị cũ từ POST (cho trường hợp lỗi)
document.addEventListener('DOMContentLoaded', function() {
    const provinceId = document.getElementById('province-select').value;
    const districtId = document.getElementById('district-select').value;
    
    // Nếu có giá trị cũ, không cần gọi AJAX lần đầu, vì PHP đã pre-populate (đoạn code ở trên)
    // Nhưng cần ensure các dropdown con bị disable nếu dropdown cha chưa được chọn
    if (!provinceId) {
        resetDropdown('district-select', 'Chọn huyện', false);
        resetDropdown('ward-select', 'Chọn xã', false);
    } else if (!districtId) {
         resetDropdown('ward-select', 'Chọn xã', false);
    }
});

</script>

<!-- Thêm liên kết đến file JavaScript mới cho logic File Preview -->
<script src="../include/js/file_preview_logic.js"></script>

