<?php
session_start();
date_default_timezone_set("Asia/Taipei");

// 讀取本機 .env 中的 Google Maps API Key
$envPath = __DIR__ . "/.env";

if (!is_readable($envPath)) {
    throw new RuntimeException("找不到環境設定檔");
}

$env = parse_ini_file($envPath, false, INI_SCANNER_RAW);

if ($env === false || empty($env["GOOGLE_MAPS_API_KEY"])) {
    throw new RuntimeException("Google Maps API Key 尚未設定");
}

$googleMapsApiKey = $env["GOOGLE_MAPS_API_KEY"];


if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

require "db.php";
require_once "upload_helper.php";
require_once __DIR__ . "/components/achievement_helper.php";

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

// 檢查使用者成就
checkAchievements($pdo, $_SESSION['user_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST["cafe_name"])
    && isset($_POST["content"]))
 {

$user_id = $_SESSION["user_id"];
$cafe_name = $_POST["cafe_name"];
$content = $_POST["content"];
$images = $_FILES["images"];
$map_url = $_POST["map_url"];
$place_id = $_POST["place_id"];

// 呼叫共用函式，驗證並儲存圖片
$image_paths = [];

try {
    $count = count($images["name"]);

    if ($count < 1 || $count > 2) {
        throw new RuntimeException("每篇貼文只能上傳 1～2 張照片");
    }

    for ($i = 0; $i < $count; $i++) {

        $image = [
            "name" => $images["name"][$i],
            "tmp_name" => $images["tmp_name"][$i],
            "error" => $images["error"][$i],
            "size" => $images["size"][$i]
        ];

        $image_paths[] = uploadImage($image);
    }

} catch (RuntimeException $e) {
    foreach ($image_paths as $path) {
        if (is_file(__DIR__ . "/" . $path)) {
            unlink(__DIR__ . "/" . $path);
        }
    }

    http_response_code(400);
    exit($e->getMessage());
}

try {
    // 開始資料庫交易
    $pdo->beginTransaction();

    // 先新增貼文
    $sql = "INSERT INTO posts
            (user_id, cafe_name, content, image, map_url, place_id)
            VALUES
            (:user_id, :cafe_name, :content, :image, :map_url, :place_id)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        "user_id" => $user_id,
        "cafe_name" => $cafe_name,
        "content" => $content,
        "image" => $image_paths[0],
        "map_url" => $map_url,
        "place_id" => $place_id
    ]);

    // 取得剛新增的貼文編號
    $post_id = $pdo->lastInsertId();

    // 將每張照片記錄到 post_images
    $imageSql = "INSERT INTO post_images
                 (post_id, image_path, sort_order)
                 VALUES (:post_id, :image_path, :sort_order)";

    $imageStmt = $pdo->prepare($imageSql);

    foreach ($image_paths as $index => $path) {
        $imageStmt->execute([
            "post_id" => $post_id,
            "image_path" => $path,
            "sort_order" => $index + 1
        ]);
    }

    // 全部成功才正式儲存
    $pdo->commit();

} catch (Throwable $e) {

    // 發生錯誤，取消資料庫交易
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // 清除這次已上傳的圖片檔案
    foreach ($image_paths as $path) {
        if (is_file(__DIR__ . "/" . $path)) {
            unlink(__DIR__ . "/" . $path);
        }
    }

    error_log($e->getMessage());
    http_response_code(500);
    exit("貼文儲存失敗，請稍後再試");
}

    header("Location: index.php");
    exit;

}
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <title>CUPMARK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ButTaiwan/iansui@main/webfont/iansui.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header class="site-header">

        <div class="header-brand">
            <h1>CUPMARK</h1>
            <p>Every cup, every mark.</p>
        </div>

        <div class="header-user">
            <span>
                歡迎回來，<?= htmlspecialchars($_SESSION["nickname"], ENT_QUOTES, "UTF-8") ?>
            </span>

            <a href="logout.php" class="logout-btn">登出</a>
        </div>

    </header>

    <div class="main-layout">

        <main class="feed">

                <form class="post-form" method="POST" enctype="multipart/form-data">

                    <!-- 發布貼文：頭像與輸入框 -->
                    <div class="post-compose">

                        <div class="post-compose-avatar">
                            <img src="images/cupmark-logo.png" alt="CUPMARK">
                        </div>

                        <textarea
                            name="content"
                            placeholder="分享你今天的咖啡時光..."
                        ></textarea>

                    </div>

                    <!-- 發布貼文：下方工具列 -->
                    <div class="post-toolbar">

                        <!-- 使用者選擇照片後的預覽區 -->
                        <div id="post-image-preview" hidden></div>

                        <!-- Google Maps 咖啡廳搜尋 -->
                        <div class="post-cafe-tool">
                            <div id="cafe-search"></div>
                        </div>

                        <!-- 上傳照片 -->
                        <div class="post-photo-tool">
                            <label for="post-image">🖼️ 上傳照片</label>
                            <input
                                type="file"
                                id="post-image"
                                name="images[]"
                                accept="image/*"
                                multiple
                            >
                        </div>

                        <!-- 發布按鈕 -->
                        <button type="submit" class="post-submit">
                            發布貼文 ↗
                        </button>

                    </div>

                    <!-- Google Maps 選取地點後的預覽 -->
                    <div id="place-preview"></div>

                    <!-- 傳送給 PHP 的地點資料 -->
                    <input type="hidden" id="cafe-name" name="cafe_name">
                    <input type="hidden" id="map-url" name="map_url">
                    <input type="hidden" id="place-id" name="place_id">

                </form>

            <script>
            (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await(a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
                key: <?= json_encode($googleMapsApiKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                v: "weekly"
            });
            </script>

            <script>
            async function initCupmark() {

                const { PlaceAutocompleteElement, Place } =
                await google.maps.importLibrary("places");

                const autocomplete = new PlaceAutocompleteElement();
                autocomplete.setAttribute("placeholder", "搜尋咖啡廳地點...");
                const container = document.getElementById("cafe-search");
                const cafeName = document.getElementById("cafe-name");
                const mapUrl = document.getElementById("map-url");
                const placeId = document.getElementById("place-id");

                container.appendChild(autocomplete);

                // 編輯貼文專用 Google Maps 搜尋框
                const editAutocomplete = new PlaceAutocompleteElement();

                const editContainer = document.getElementById("edit-cafe-search");

                editContainer.appendChild(editAutocomplete);

                editAutocomplete.addEventListener("gmp-select", async (event) => {

                    const place = event.placePrediction.toPlace();

                    await place.fetchFields({
                        fields: ["displayName", "googleMapsURI", "id"]
                    });

                    document.getElementById("edit-cafe-name").value = place.displayName;
                    document.getElementById("edit-map-url").value = place.googleMapsURI;
                    document.getElementById("edit-place-id").value = place.id;

                    document.getElementById("edit-selected-cafe").textContent = place.displayName;

                    console.log("編輯選擇的咖啡廳：", place.displayName);

                });

                autocomplete.addEventListener("gmp-select", async (event) => {

                    const place = event.placePrediction.toPlace();

                    await place.fetchFields({
                        fields: ["displayName", "googleMapsURI", "photos", "id"]
                    });

                    cafeName.value = place.displayName;
                    mapUrl.value = place.googleMapsURI;
                    placeId.value = place.id;

                    console.log(place.displayName);
                    console.log(place.googleMapsURI);
                    console.log(place.id);
                    console.log(place.photos[0]);
                    const photoUrl = place.photos[0].getURI({
                        maxWidth: 400

                    });

                    console.log(photoUrl);
                }); 

                const placeCards = document.querySelectorAll(".place-card");

                console.log(placeCards);

                placeCards.forEach(async (card) => {

                    const placeId = card.dataset.placeId;

                    if (!placeId) {
                        return;
                    }
                    console.log(placeId);

                    const savedPlace = new Place({
                    id: placeId
                    });

                    await savedPlace.fetchFields({
                        fields: ["photos"]
                    });

                    console.log(savedPlace.photos);
                    const firstPhoto = savedPlace.photos[0];
                    console.log(firstPhoto);
                    const photoUrl = firstPhoto.getURI({
                        maxWidth: 400
                    });
                    console.log(photoUrl);
                    const photoImg = document.createElement("img");
                    photoImg.src = photoUrl;
                    photoImg.alt = "咖啡廳地標照片";
                    photoImg.classList.add("place-photo");

                    card.prepend(photoImg);
                });

                const menuButtons = document.querySelectorAll(".post-menu-btn");

                menuButtons.forEach((button) => {

                    button.addEventListener("click", () => {

                        const menu = button.nextElementSibling;

                        menu.hidden = !menu.hidden; 

                    });
                });

                const deleteButtons = document.querySelectorAll(".delete-btn");

                deleteButtons.forEach((button) => {

                    button.addEventListener("click", () => {

                        const postId = button.dataset.postId;

                        if (!confirm("確定要刪除這篇貼文嗎？")) {
                        return;
                        }

                        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                        console.log(postId);
                        console.log(csrfToken);

                        fetch("delete_post.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: new URLSearchParams({
                                post_id: postId,
                                csrf_token: csrfToken
                            })
                        })
                        .then((response) => {
                            if (response.ok) {
                                location.reload();
                            } else {
                                alert("刪除失敗！");
                            }
                        })
                        .catch(() => {
                            alert("連線失敗！");
                        });

                    });

                });

                const editButtons = document.querySelectorAll(".edit-btn");
                const editModal = document.getElementById("edit-modal");

                editButtons.forEach((button) => {

                button.addEventListener("click", () => {

                    const postId = button.dataset.postId;
                    const content = button.dataset.content;
                    const image = button.dataset.image;
                    const cafeName = button.dataset.cafeName;
                    document.getElementById("edit-selected-cafe").textContent = cafeName;
                    const mapUrl = button.dataset.mapUrl;
                    const placeId = button.dataset.placeId;

                    document.getElementById("edit-post-id").value = postId;
                    document.getElementById("edit-content").value = content;
                    document.getElementById("edit-current-image").src = image;
                    document.getElementById("edit-cafe-name").value = cafeName;
                    document.getElementById("edit-map-url").value = mapUrl;
                    document.getElementById("edit-place-id").value = placeId;

                    editModal.showModal();

                });

                });

                // 取得編輯表單
                const editForm = document.getElementById("edit-form");

                // 監聽儲存修改
                editForm.addEventListener("submit", async (event) => {

                // 阻止表單預設送出
                event.preventDefault();

                // 收集表單資料
                const formData = new FormData(editForm);

                // 加入 CSRF Token
                const csrfToken = document.querySelector(
                    'meta[name="csrf-token"]'
                ).content;

                formData.append("csrf_token", csrfToken);

                // 傳送資料到 PHP
                const response = await fetch("update_post.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.text();

                if (response.ok && result.trim() === "success") {
                    alert("貼文修改成功！");
                    window.location.reload();
                } else {
                    alert("修改失敗：" + result);
                }

                });

            }

            initCupmark();
            </script>

            <script>
                const postImageInput = document.getElementById("post-image");
                const postImagePreview = document.getElementById("post-image-preview");

                let previewUrls = [];

                // 更新照片預覽
                function renderImagePreview() {

                    // 清除舊的預覽網址
                    previewUrls.forEach((url) => {
                        URL.revokeObjectURL(url);
                    });

                    previewUrls = [];
                    postImagePreview.innerHTML = "";

                    const files = Array.from(postImageInput.files);

                    files.forEach((file, index) => {

                        const imageUrl = URL.createObjectURL(file);
                        previewUrls.push(imageUrl);

                        // 每張照片的外層容器
                        const item = document.createElement("div");
                        item.className = "preview-item";

                        // 照片
                        const img = document.createElement("img");
                        img.src = imageUrl;
                        img.alt = "貼文照片預覽";

                        // 移除按鈕
                        const removeBtn = document.createElement("button");
                        removeBtn.type = "button";
                        removeBtn.className = "preview-remove";
                        removeBtn.textContent = "×";

                        removeBtn.addEventListener("click", () => {
                            removeImage(index);
                        });

                        item.appendChild(img);
                        item.appendChild(removeBtn);
                        postImagePreview.appendChild(item);
                    });

                    postImagePreview.hidden = files.length === 0;
                }

                // 真正移除待上傳的照片
                function removeImage(removeIndex) {

                    const files = Array.from(postImageInput.files);
                    const dataTransfer = new DataTransfer();

                    files.forEach((file, index) => {
                        if (index !== removeIndex) {
                            dataTransfer.items.add(file);
                        }
                    });

                    // 更新真正要上傳的檔案
                    postImageInput.files = dataTransfer.files;

                    // 同步更新縮圖
                    renderImagePreview();
                }

                // 使用者選擇照片
                postImageInput.addEventListener("change", () => {

                    if (postImageInput.files.length > 2) {
                        alert("每篇貼文最多只能上傳兩張照片！");
                        postImageInput.value = "";
                    }

                    renderImagePreview();
                });
            </script>

            <?php

                $sql = "SELECT posts.*, users.nickname
                    FROM posts
                    JOIN users ON posts.user_id = users.id
                    WHERE posts.is_deleted = 0
                    ORDER BY posts.created_at DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute();

                $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($posts as $post) {
                echo "<div class='post'>";

                echo "<div class='post-header'>";

                echo "<div class='post-avatar'>";
                echo "<img src='images/cupmark-logo.png' alt='CUPMARK 頭像'>";
                echo "</div>";

                echo "<div class='post-author'>";
                echo "<h3>" . htmlspecialchars($post["nickname"], ENT_QUOTES, "UTF-8") . "</h3>";
                echo "<small>" . htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8") . "</small>";
                echo "</div>";

                $createdTime = strtotime($post["created_at"]);
                $currentTime = time();

                $canEdit = ($currentTime - $createdTime) <= 15 * 60
                        && $currentTime >= $createdTime;

                if ($post["user_id"] == $_SESSION["user_id"]) {
                echo "<button class='post-menu-btn'>•••</button>";
                echo "<div class='post-menu' hidden>";

                if ($canEdit) {
                echo "<button class='edit-btn'
                    data-post-id='" . $post["id"] . "'
                    data-content='" . htmlspecialchars($post["content"], ENT_QUOTES, "UTF-8") . "'
                    data-image='" . htmlspecialchars($post["image"], ENT_QUOTES, "UTF-8") . "'
                    data-cafe-name='" . htmlspecialchars($post["cafe_name"], ENT_QUOTES, "UTF-8") . "'
                    data-map-url='" . htmlspecialchars($post["map_url"], ENT_QUOTES, "UTF-8") . "'
                    data-place-id='" . htmlspecialchars($post["place_id"], ENT_QUOTES, "UTF-8") . "'>
                    編輯
                </button>";
                }

                // 刪除按鈕仍放在三點選單內
                echo "<button class='delete-btn' data-post-id='" . $post["id"] . "'>刪除</button>";

                echo "</div>"; // 關閉 post-menu
                } // 關閉作者判斷 if

                echo "</div>"; // 關閉 post-header

                // 貼文內容
                echo "<p class='post-content'>" . htmlspecialchars($post["content"], ENT_QUOTES, "UTF-8") . "</p>";

                // 查詢這篇貼文的所有照片
                $imageSql = "SELECT image_path
                             FROM post_images
                             WHERE post_id = :post_id
                             ORDER BY sort_order ASC";

                $imageStmt = $pdo->prepare($imageSql);
                $imageStmt->execute([
                    "post_id" => $post["id"]
                ]);

                $postImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

                // 顯示貼文照片
                echo "<div class='post-media'>";

                foreach ($postImages as $image) {
                    echo "<img class='post-image'
                            src='" . htmlspecialchars($image["image_path"], ENT_QUOTES, "UTF-8") . "'
                            alt='貼文照片'>";
                }

                // 咖啡廳地標
                echo "<div class='place-card' data-place-id='" . htmlspecialchars($post["place_id"], ENT_QUOTES, "UTF-8") . "'>";

                echo "<div class='place-info'>";

                echo "<div class='place-name'>"
                    . htmlspecialchars($post["cafe_name"], ENT_QUOTES, "UTF-8")
                    . "</div>";

                echo "<a class='place-map-link'
                        href='" . htmlspecialchars($post["map_url"], ENT_QUOTES, "UTF-8") . "'
                        target='_blank'
                        rel='noopener noreferrer'>
                        📍 在 Google Maps 查看 ↗
                    </a>";

                echo "</div>"; // 關閉 place-info

                echo "</div>"; // 關閉 place-card
                echo "</div>"; // 關閉 post-media

                echo "</div>"; // 關閉 post
                } // 關閉 foreach
            ?>

        </main>

        <?php require_once __DIR__ . "/components/sidebar.php"; ?>

    </div>

    <dialog id="edit-modal">

        <h2>編輯貼文</h2>

        <form id="edit-form" enctype="multipart/form-data">

            <input type="hidden" name="post_id" id="edit-post-id">

            <p>咖啡廳名稱</p>

            <p>目前選擇：<span id="edit-selected-cafe"></span></p>

            <div id="edit-cafe-search"></div>
            <div id="edit-place-preview"></div>

            <input type="hidden" name="cafe_name" id="edit-cafe-name">
            <input type="hidden" name="map_url" id="edit-map-url">
            <input type="hidden" name="place_id" id="edit-place-id">

            <p>分享內容</p>
            <textarea name="content" id="edit-content"></textarea>

            <p>目前照片</p>
            <img id="edit-current-image" alt="目前貼文照片" width="150">

            <p>更換照片（不選擇則保留原圖）</p>
            <input type="file" name="image" id="edit-image" accept="image/*">

            <button type="button" id="cancel-edit">取消</button>
            <button type="submit">儲存修改</button>

        </form>

    </dialog>

</body>
</html>