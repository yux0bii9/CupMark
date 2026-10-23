<?php
session_start();
require "db.php";

if (
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& isset($_POST["username"])
	&& isset($_POST["password"]))

{

$username = $_POST["username"];
$password = $_POST["password"];

$sql = "SELECT * FROM users WHERE username = :username";
$stmt = $pdo->prepare($sql);
$stmt->execute(["username" => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user["password"])) {
    $_SESSION["user_id"] = $user["id"];    
    $_SESSION["username"] = $user["username"];
    $_SESSION["nickname"] = $user["nickname"];

	$_SESSION["login_animation_pending"] = true;

    header("Location: login_success.php");
    exit;
} else {
    $error = "帳號密碼錯誤，請重新輸入";
}

}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 | CUPMARK</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-page">

    <div class="auth-card">

        <h1 class="auth-title">CUPMARK</h1>
        <p class="auth-subtitle">Every cup, every mark.</p>

        <?php if (!empty($error)): ?>
            <p class="auth-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <?php if (isset($_GET["registered"])): ?>
            <p class="auth-success">
                註冊成功！請登入
            </p>
        <?php endif; ?>

        <form class="auth-form" method="POST">

			<div class="auth-field">
				<label for="username">使用者帳號</label>
				<input
					type="text"
					id="username"
					name="username"
					placeholder="請輸入帳號"
					autocomplete="username"
					required
				>
			</div>

			<div class="auth-field">
				<label for="password">密碼</label>
				<input
					type="password"
					id="password"
					name="password"
					placeholder="請輸入密碼"
					autocomplete="current-password"
					required
				>
			</div>

			<button type="submit" class="auth-button">登入</button>

		</form>

        <div class="auth-footer">
            還沒有帳號？
            <a href="register.php">註冊新帳號</a>
        </div>

    </div>

</body>
</html>