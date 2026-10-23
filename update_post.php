<?php

session_start();
date_default_timezone_set("Asia/Taipei");
require_once "db.php";
require_once "upload_helper.php";

// filter_var()檢查 post_id 是否為有效的整數,避免收到不合理的資料
$post_id = filter_var(
    $_POST["post_id"] ?? null,
    FILTER_VALIDATE_INT
);

if ($post_id === false || $post_id === null || $post_id <= 0) {
    http_response_code(400);
    exit("Invalid Post ID");
}

$sql = "SELECT *
        FROM posts
        WHERE id = :post_id
        AND user_id = :user_id
        AND is_deleted = 0";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "post_id" => $post_id,
    "user_id" => $_SESSION["user_id"]
]);

$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    exit("Post Not Found");
}

// 後端再次驗證編輯時間,超過15分鐘後台資料庫無法update
$createdTime = strtotime($post["created_at"]);
$currentTime = time();

if (
    $createdTime === false ||
    $currentTime < $createdTime ||
    ($currentTime - $createdTime) > 15 * 60
) {
    http_response_code(403);
    exit("Editing time expired");
}

// 取得編輯後的貼文資料
$content = trim($_POST["content"] ?? "");
$cafe_name = trim($_POST["cafe_name"] ?? "");
$map_url = trim($_POST["map_url"] ?? "");
$place_id = trim($_POST["place_id"] ?? "");

// 確認必要資料沒有空白
if (
    $content === "" ||
    $cafe_name === "" ||
    $map_url === "" ||
    $place_id === ""
) {
    http_response_code(400);
    exit("Missing Required Fields");
}

// 預設保留原本的照片
$image_path = $post["image"];

// 有上傳新照片時，呼叫共用圖片處理函式
if (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
) {
    try {
        $image_path = uploadImage($_FILES["image"]);
    } catch (RuntimeException $e) {
        http_response_code(400);
        exit($e->getMessage());
    }
}

// 更新貼文資料
$sql = "UPDATE posts
        SET cafe_name = :cafe_name,
            content = :content,
            image = :image,
            map_url = :map_url,
            place_id = :place_id
        WHERE id = :post_id
        AND user_id = :user_id
        AND is_deleted = 0
        AND created_at >= :edit_deadline";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "cafe_name" => $cafe_name,
    "content" => $content,
    "image" => $image_path,
    "map_url" => $map_url,
    "place_id" => $place_id,
    "post_id" => $post_id,
    "user_id" => $_SESSION["user_id"],
    "edit_deadline" => date("Y-m-d H:i:s", time() - 15 * 60)
]);

echo "success";