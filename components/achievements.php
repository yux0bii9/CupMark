<?php

require_once __DIR__ . "/achievement_config.php";

// 查詢目前使用者已解鎖的成就
$stmt = $pdo->prepare("
    SELECT achievement_id, is_seen
    FROM user_achievements
    WHERE user_id = :user_id
");

$stmt->execute([
    ':user_id' => $_SESSION['user_id']
]);

$unlocked = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<section class="achievement-card">

    <h2>🏆 我的成就</h2>

    <div class="achievement-list">

        <?php foreach ($achievements as $id => $name): ?>

            <?php
                $isUnlocked = array_key_exists($id, $unlocked);
                $isNew = $isUnlocked && $unlocked[$id] == 0;
            ?>

            <div
                class="achievement-item"
                data-achievement-id="<?= $id ?>"
                data-achievement-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                data-achievement-description="<?= htmlspecialchars($achievementDescriptions[$id] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                data-unlocked="<?= $isUnlocked ? '1' : '0' ?>"
                >

                <?php if ($isUnlocked): ?>

               <img
                    src="images/achievement_<?= (int)$id ?>.png?v=2"
                    alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                    class="achievement-icon"
                    style="width:60px; height:60px; object-fit:contain;"
                >

                <p><?= htmlspecialchars($name) ?></p>

                <?php if ($isNew): ?>
                    <span class="achievement-new"></span>
                <?php endif; ?>

                <?php else: ?>

                    <img
                        src="images/achievement_0.png?v=2"
                        alt="尚未解鎖"
                        class="achievement-icon"
                        style="width:60px; height:60px; object-fit:contain;"
                    >

                    <p>???</p>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

</section>

<div id="achievement-modal" hidden>

    <div class="achievement-modal-content">

        <button type="button" id="achievement-close">×</button>

        <div class="achievement-modal-body">

            <img
                id="achievement-modal-image"
                src=""
                alt="成就圖片"
                style="width:180px; height:180px; object-fit:contain; flex-shrink:0;"
            >

            <div class="achievement-modal-text">

                <h2 id="achievement-modal-title"></h2>

                <p id="achievement-modal-description"></p>

            </div>

        </div>

    </div>

</div>

<script>
const achievementItems = document.querySelectorAll(".achievement-item");

const achievementModal = document.getElementById("achievement-modal");
const achievementTitle = document.getElementById("achievement-modal-title");
const achievementDescription = document.getElementById("achievement-modal-description");
const achievementImage = document.getElementById("achievement-modal-image");
const achievementClose = document.getElementById("achievement-close");

achievementItems.forEach((item) => {

    item.addEventListener("click", () => {

        if (item.dataset.unlocked !== "1") {
            return;
        }

            // 成就名稱
            achievementTitle.textContent = item.dataset.achievementName;

            // 成就說明
            achievementDescription.textContent = item.dataset.achievementDescription;

            // 成就圖片
            achievementImage.src =
                `images/achievement_${item.dataset.achievementId}.png?v=2`;

            // 記錄目前點擊的成就 ID
            currentAchievementId = item.dataset.achievementId;

        achievementModal.hidden = false;

    });

});

let currentAchievementId = null;

achievementClose.addEventListener("click", async () => {

    if (currentAchievementId === null) {
        return;
    }

    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]'
    ).content;

    const formData = new FormData();

    formData.append("achievement_id", currentAchievementId);
    formData.append("csrf_token", csrfToken);

    try {
        const response = await fetch("mark_achievement_seen.php", {
            method: "POST",
            body: formData
        });

        if (!response.ok) {
            throw new Error("更新已讀狀態失敗");
        }

        const result = await response.json();

        if (result.success) {
            const item = document.querySelector(
                `.achievement-item[data-achievement-id="${currentAchievementId}"]`
            );

            item?.querySelector(".achievement-new")?.remove();
        }

    } catch (error) {
        console.error(error);
    }

    achievementModal.hidden = true;
    currentAchievementId = null;

});
</script>