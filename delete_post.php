<?php

session_start();

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    exit;
}

$postId = $_POST["post_id"] ?? null;

$postId = filter_var($postId, FILTER_VALIDATE_INT);

if ($postId === false || $postId <= 0) {
    http_response_code(400);
    exit;
}

$csrfToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($csrfToken) ||
    !hash_equals($_SESSION["csrf_token"] ?? "", $csrfToken)
) {
    http_response_code(403);
    exit;
}

$sql = "UPDATE posts
        SET is_deleted = 1,
            deleted_at = NOW()
        WHERE id = ?
          AND user_id = ?
          AND is_deleted = 0";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $postId,
    $_SESSION["user_id"]
]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    exit;
}

http_response_code(200);