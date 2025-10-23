<?php
session_start();
include __DIR__ . '/../include/conn.php';

if(!isset($_SESSION['user_id'])){
    die("Bạn chưa đăng nhập.");
}

$user_id = $_SESSION['user_id'];

// =================== Kiểm tra subscription ===================
$allow_video = false;
$allow_priority = false;

$sql_sub = "SELECT package_id 
            FROM User_Subscriptions 
            WHERE user_id = ? AND is_current = 1 AND status = 1
            LIMIT 1";

$stmt = $conn->prepare($sql_sub);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$subscription = $res->fetch_assoc();
$stmt->close();

if($subscription){
    $pkg_id = $subscription['package_id'];

    $sql_pkg = "SELECT feature_video_allowed, is_priority_display 
                FROM Promotion_Packages 
                WHERE package_id = ? AND status = 1 LIMIT 1";
    $stmt2 = $conn->prepare($sql_pkg);
    $stmt2->bind_param("i", $pkg_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $pkg = $res2->fetch_assoc();
    $stmt2->close();

    if($pkg){
        $allow_video = boolval($pkg['feature_video_allowed']);
        $allow_priority = boolval($pkg['is_priority_display']);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Test Upload Video</title>
</head>
<body>
<h2>Test Upload Video</h2>

<?php if($allow_video): ?>
    <form method="POST" enctype="multipart/form-data" action="upload_video.php">
        <input type="file" name="videos[]" multiple>
        <button type="submit">Upload Video</button>
    </form>
    <?php if($allow_priority): ?>
        <p style="color:green;">Bạn cũng có quyền ưu tiên hiển thị bài đăng.</p>
    <?php endif; ?>
<?php else: ?>
    <p style="color:red;">Bạn chưa có gói cho phép đăng video hoặc gói đã hết hiệu lực.</p>
<?php endif; ?>

</body>
</html>
