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
$province_filter = intval($_GET['province'] ?? 0);
$district_filter = intval($_GET['district'] ?? 0);
$ward_filter = intval($_GET['ward'] ?? 0);

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
    if(!$price) $errors[] = "Giá phải lớn hơn 0.";
    if(!$province_id || !$district_id || !$ward_id) $errors[] = "Vui lòng chọn đầy đủ Tỉnh/Huyện/Xã.";
    if(!$detailed_address) $errors[] = "Vui lòng nhập địa chỉ chi tiết.";

    // =================== Lưu post ===================
    if(empty($errors)){
        $stmt = $conn->prepare("INSERT INTO posts (user_id, province_id, district_id, ward_id, title, description, price, deposit, area, detailed_address, is_priority_post, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?, 'pending', NOW())");
        $stmt->bind_param("iiissdiddsi", $user_id, $province_id, $district_id, $ward_id, $title, $description, $price, $deposit, $area, $detailed_address, $is_priority_post);
        if($stmt->execute()){
            $post_id = $stmt->insert_id;

            // =================== Upload nhiều ảnh ===================
            if(!empty($_FILES['images']['name'][0])){
                $allowed = ['jpg','jpeg','png','gif'];
                foreach($_FILES['images']['name'] as $k => $name){
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if(!in_array($ext, $allowed)) continue;
                    $path = 'upload/img_post/'.uniqid().'.'.$ext;
                    if(move_uploaded_file($_FILES['images']['tmp_name'][$k], __DIR__ . '/../'.$path)){
                        $stmt_img = $conn->prepare("INSERT INTO image_post (id_post, link) VALUES (?, ?)");
                        $stmt_img->bind_param("is", $post_id, $path);
                        $stmt_img->execute();
                        $stmt_img->close();
                    }
                }
            }

            // =================== Upload video nếu gói cho phép ===================
            if($allow_video && !empty($_FILES['videos']['name'][0])){
                $allowed_vid = ['mp4','mov','avi','mkv'];
                foreach($_FILES['videos']['name'] as $k => $name){
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if(!in_array($ext, $allowed_vid)) continue;
                    $path = 'upload/video_post/'.uniqid().'.'.$ext;
                    if(move_uploaded_file($_FILES['videos']['tmp_name'][$k], __DIR__ . '/../'.$path)){
                        $stmt_vid = $conn->prepare("INSERT INTO video_post (id_post, link) VALUES (?, ?)");
                        $stmt_vid->bind_param("is", $post_id, $path);
                        $stmt_vid->execute();
                        $stmt_vid->close();
                    }
                }
            }

            $success_message = "Đăng tin thành công!";
        } else {
            $errors[] = "Lỗi lưu dữ liệu: ".$conn->error;
        }
        $stmt->close();
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/detail_admin.css">
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

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

    <form method="POST" enctype="multipart/form-data" class="detail-wrapper">
        <div class="detail-left">
            <div class="detail-item">
                <span class="label">Tiêu đề:</span>
                <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <div class="detail-item">
                <span class="label">Mô tả:</span>
                <textarea name="description" id="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="detail-item">
                <span class="label">Giá (VNĐ):</span>
                <input type="number" name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
            </div>

            <div class="detail-item">
                <span class="label">Tiền cọc:</span>
                <input type="number" name="deposit" value="<?php echo htmlspecialchars($_POST['deposit'] ?? ''); ?>">
            </div>

            <div class="detail-item">
                <span class="label">Diện tích (m²):</span>
                <input type="number" name="area" value="<?php echo htmlspecialchars($_POST['area'] ?? ''); ?>">
            </div>

            <div class="detail-item">
                <span class="label">Địa chỉ chi tiết:</span>
                <input type="text" name="detailed_address" value="<?php echo htmlspecialchars($_POST['detailed_address'] ?? ''); ?>">
            </div>

            <!-- Tỉnh/Huyện/Xã -->
            <div class="detail-item">
                <span class="label">Tỉnh:</span>
                <select name="province" onchange="location.href='?province='+this.value;">
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
                <select name="district" onchange="location.href='?province=<?php echo $province_filter; ?>&district='+this.value;">
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
                <select name="ward">
                    <option value="">Chọn xã</option>
                    <?php foreach($wards as $w): ?>
                        <option value="<?php echo $w['ward_id']; ?>" <?php if($w['ward_id']==$ward_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($w['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            
            <!-- Upload ảnh & video -->
            <div class="detail-item">
                <span class="label">Ảnh (có thể thêm nhiều):</span>
                <div id="image-wrapper">
                    <input type="file" name="images[]" multiple>
                </div>
                <button type="button" onclick="addImageInput()">➕ Thêm ảnh</button>
            </div>

            <?php if($allow_video): ?>
            <div class="detail-item">
                <span class="label">Video (có thể thêm nhiều nếu gói cho phép):</span>
                <div id="video-wrapper">
                    <input type="file" name="videos[]" multiple>
                </div>
                <button type="button" onclick="addVideoInput()">➕ Thêm video</button>
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
function addImageInput() {
    let wrapper = document.getElementById('image-wrapper');
    let input = document.createElement('input');
    input.type = 'file';
    input.name = 'images[]';
    input.multiple = true;
    wrapper.appendChild(input);
}
</script>
