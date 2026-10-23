<?php

// 將已解鎖的成就寫入資料庫
function unlockAchievement($pdo, $userId, $achievementId) {

    $sql = "
        INSERT IGNORE INTO user_achievements
        (user_id, achievement_id)
        VALUES (:user_id, :achievement_id)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId,
        ':achievement_id' => $achievementId
    ]);

    return $stmt->rowCount() > 0;
}


// 檢查使用者符合哪些成就
function checkAchievements($pdo, $userId) {

    // 成就 1：初來乍到
    unlockAchievement($pdo, $userId, 1);

    // 統計累積發文數，包含軟刪除的貼文
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM posts
        WHERE user_id = :user_id
    ");

    $stmt->execute([':user_id' => $userId]);
    $postCount = (int) $stmt->fetchColumn();

    // 成就 2：來喝第一杯
    if ($postCount >= 1) {
        unlockAchievement($pdo, $userId, 2);
    }

    // 成就 3：無三不成咖
    if ($postCount >= 3) {
        unlockAchievement($pdo, $userId, 3);
    }

    // 成就 5：分享欲達人
    if ($postCount >= 5) {
        unlockAchievement($pdo, $userId, 5);
    }

    // 成就 8：咖啡因成癮
    if ($postCount >= 10) {
        unlockAchievement($pdo, $userId, 8);
    }

    // 成就 4：有點曖昧
    // 曾與其他使用者分享相同的 Google Maps 地點
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM posts AS mine
        JOIN posts AS others
          ON mine.place_id = others.place_id
        WHERE mine.user_id = :user_id
          AND others.user_id != :user_id
          AND mine.place_id IS NOT NULL
          AND mine.place_id != ''
    ");

    $stmt->execute([':user_id' => $userId]);

    if ((int) $stmt->fetchColumn() > 0) {
        unlockAchievement($pdo, $userId, 4);
    }

    // 成就 6、7：依發文時間解鎖
    $stmt = $pdo->prepare("
        SELECT
            SUM(
                TIME(created_at) >= '23:00:00'
                OR TIME(created_at) < '04:00:00'
            ) AS night_posts,

            SUM(
                TIME(created_at) >= '05:00:00'
                AND TIME(created_at) < '09:00:00'
            ) AS morning_posts
        FROM posts
        WHERE user_id = :user_id
    ");

    $stmt->execute([':user_id' => $userId]);
    $timePosts = $stmt->fetch(PDO::FETCH_ASSOC);

    // 成就 6：喵嗚Mewo!
    if (($timePosts['night_posts'] ?? 0) > 0) {
        unlockAchievement($pdo, $userId, 6);
    }

    // 成就 7：阿姨~~蛋餅加大
    if (($timePosts['morning_posts'] ?? 0) > 0) {
        unlockAchievement($pdo, $userId, 7);
    }

    // 成就 9：杯中傳奇人物
    // 前八個成就全部解鎖
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT achievement_id)
        FROM user_achievements
        WHERE user_id = :user_id
          AND achievement_id BETWEEN 1 AND 8
    ");

    $stmt->execute([':user_id' => $userId]);

    if ((int) $stmt->fetchColumn() === 8) {
        unlockAchievement($pdo, $userId, 9);
    }
}