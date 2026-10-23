<?php

// 取得目前登入者的 ID
$userId = $_SESSION["user_id"];

// 查詢使用者暱稱
$sql = "SELECT nickname, username
        FROM users
        WHERE id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(["user_id" => $userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 計算目前有效的貼文數量
$sql = "SELECT COUNT(*) FROM posts
        WHERE user_id = :user_id
        AND is_deleted = 0";

$stmt = $pdo->prepare($sql);
$stmt->execute(["user_id" => $userId]);

$postCount = $stmt->fetchColumn();

// 計算已解鎖成就數量
$sql = "SELECT COUNT(*)
        FROM user_achievements
        WHERE user_id = :user_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(["user_id" => $userId]);

$achievementCount = (int) $stmt->fetchColumn();


// 計算探索過的不同咖啡廳數量
$sql = "SELECT COUNT(DISTINCT place_id)
        FROM posts
        WHERE user_id = :user_id
        AND is_deleted = 0
        AND place_id IS NOT NULL
        AND place_id != ''";

$stmt = $pdo->prepare($sql);
$stmt->execute(["user_id" => $userId]);

$cafeCount = (int) $stmt->fetchColumn();
?>

<section class="profile-card">

   <div class="profile-header">

        <div class="profile-avatar">
            <img src="images/cupmark-logo.png" alt="CUPMARK Logo">
        </div>

        <div class="profile-identity">
            <strong>
                <?= htmlspecialchars($user["nickname"] ?? "", ENT_QUOTES, "UTF-8") ?>
            </strong>

            <span class="profile-username">
                @<?= htmlspecialchars($user["username"] ?? "", ENT_QUOTES, "UTF-8") ?>
            </span>
        </div>

    </div>

    <div class="profile-stats">

        <div>
            <strong><?= $postCount ?></strong>
            <p>分享貼文</p>
        </div>

        <div>
            <strong><?= $achievementCount ?></strong>
            <p>解鎖成就</p>
        </div>

        <div>
            <strong><?= $cafeCount ?></strong>
            <p>探索咖啡廳</p>
        </div>

    </div>

</section>