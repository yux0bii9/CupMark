<?php
session_start();

require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=utf-8");

// 只接受 POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit;
}

// 確認使用者已登入
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    exit;
}

// 驗證 CSRF Token
$csrfToken = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $csrfToken)
) {
    http_response_code(403);
    exit;
}

// 取得成就編號
$achievementId = filter_input(
    INPUT_POST,
    "achievement_id",
    FILTER_VALIDATE_INT
);

if (!$achievementId || $achievementId < 1 || $achievementId > 9) {
    http_response_code(400);
    exit;
}

// 更新已讀狀態
$stmt = $pdo->prepare("
    UPDATE user_achievements
    SET is_seen = 1
    WHERE user_id = :user_id
      AND achievement_id = :achievement_id
");

$stmt->execute([
    ":user_id" => $_SESSION["user_id"],
    ":achievement_id" => $achievementId
]);

echo json_encode(["success" => true]);