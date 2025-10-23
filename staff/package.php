<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/staff_header.php';
include __DIR__ . '/../include/staff_sidebar.php';

// Lấy các gói đang mở (status=1)
$sql = "SELECT * FROM Promotion_Packages WHERE status = 1 ORDER BY is_priority_display DESC, price ASC";
$result = $conn->query($sql);
$packages = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $packages[] = $row;
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/staff_package.css">

<div class="main-content">
    <h2 class="page-title">Chọn gói quảng cáo</h2>
    <div class="packages-wrapper">
        <?php if (!empty($packages)): ?>
            <?php foreach($packages as $pkg): ?>
                <div class="package-card <?php echo $pkg['is_priority_display'] ? 'highlight' : ''; ?>">
                    <?php if($pkg['is_priority_display']): ?>
                        <span class="badge-priority">🌟 Ưu tiên</span>
                    <?php endif; ?>
                    <?php if($pkg['feature_video_allowed']): ?>
                        <span class="badge-video">🎬 Video</span>
                    <?php endif; ?>

                    <h3 class="package-name"><?php echo htmlspecialchars($pkg['name']); ?></h3>
                    <p class="package-price"><?php echo number_format($pkg['price'],0,'',','); ?> VNĐ</p>
                    <p class="package-duration">Thời hạn: <?php echo $pkg['duration_days']; ?> ngày</p>
                    <a href="subscribe_package.php?id=<?php echo $pkg['package_id']; ?>" class="btn-subscribe">Chọn gói</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Hiện tại chưa có gói nào.</p>
        <?php endif; ?>
    </div>
</div>
