<?php
session_start();
require "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $nickname = trim($_POST["nickname"] ?? "");

    if ($username === "" || $password === "" || $nickname === "") {
        $error = "請填寫所有欄位";

    } elseif (strlen($username) > 10) {
        $error = "帳號不可超過 10 個字元";

    } elseif (strlen($password) < 8) {
        $error = "密碼至少需要 8 個字元";

    } else {

        $sql = "SELECT id FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["username" => $username]);

        if ($stmt->fetch()) {
            $error = "這個帳號已經有人使用囉！";

        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (username, password, nickname)
                    VALUES (:username, :password, :nickname)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                "username" => $username,
                "password" => $hashed_password,
                "nickname" => $nickname
            ]);

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊 | CUPMARK</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-page">

    <div class="auth-card">

        <h1 class="auth-title">CUPMARK</h1>
        <p class="auth-subtitle">Every cup, every mark.</p>

        <?php if ($error !== ""): ?>
            <p class="auth-error">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form class="auth-form" method="POST">

            <div class="auth-field">
                <label for="username">使用者帳號</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="請輸入帳號（最多10個字元）"
                    autocomplete="username"
                    maxlength="10"
                    required
                >
            </div>

            <div class="auth-field">
                <label for="password">密碼</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="請輸入密碼（至少8個字元）"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
            </div>

            <div class="auth-field">
                <label for="nickname">暱稱</label>
                <input
                    type="text"
                    id="nickname"
                    name="nickname"
                    placeholder="請輸入暱稱"
                    required
                >
            </div>

            <button type="submit" class="auth-button">
                註冊
            </button>

        </form>

        <div class="auth-footer">
            已經有帳號了？
            <a href="login.php">返回登入</a>
        </div>

    </div>

</body>
</html>