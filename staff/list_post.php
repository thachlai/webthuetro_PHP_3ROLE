<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/staff_header.php';
include __DIR__ . '/../include/staff_sidebar.php';

// Kiểm tra login & role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 2){
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách bài đăng còn hiệu lực của user
$sql = "SELECT p.id_post, p.title, p.description,
               (SELECT link FROM image_post WHERE id_post = p.id_post LIMIT 1) as thumb
        FROM posts p
        WHERE p.user_id=? AND p.status='active'
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$posts = [];
while($row = $result->fetch_assoc()){
    $posts[] = $row;
}
$stmt->close();
?>

<link rel="stylesheet" href="../css/main.css">
<style>
.post-list { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; }
.post-item {
    display: flex;
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.post-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
.post-thumb { width: 150px; height: 100px; object-fit: cover; flex-shrink: 0; }
.post-content { padding: 10px 15px; display: flex; flex-direction: column; justify-content: center; }
.post-title { font-size: 16px; font-weight: bold; margin-bottom: 5px; color: #333; }
.post-desc { font-size: 14px; color: #666; }
</style>

<div class="main-content">
    <br> <br>
    <h2 class="page-title">Danh sách bài đăng</h2>
    <div class="post-list">
        <?php if(empty($posts)): ?>
            <p>Chưa có bài đăng nào.</p>
        <?php else: ?>
            <?php foreach($posts as $post): ?>
                <div class="post-item" onclick="location.href='staff_post_detail.php?id=<?php echo $post['id_post']; ?>'">
                    <?php if($post['thumb']): ?>
                        <img src="../<?php echo $post['thumb']; ?>" alt="Ảnh" class="post-thumb">
                    <?php else: ?>
                        <div class="post-thumb" style="background:#eee; display:flex; align-items:center; justify-content:center; color:#999;">No Image</div>
                    <?php endif; ?>
                    <div class="post-content">
                        <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                        <div class="post-desc"><?php echo htmlspecialchars(mb_strimwidth($post['description'],0,120,'...')); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
