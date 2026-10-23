<?php
session_start();

// 未登入不能進入動畫頁面
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 防止動畫頁面被重新整理後重複播放
if (empty($_SESSION["login_animation_pending"])) {
    header("Location: index.php");
    exit;
}

unset($_SESSION["login_animation_pending"]);

?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | CUPMARK</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="welcome-page">

    <div class="welcome-animation">

        <div class="welcome-cups">

            <div class="welcome-cup welcome-cup-left">
                <svg viewBox="0 0 100 100">
                    <path d="M20 30 H70 V65 Q70 80 55 80 H35 Q20 80 20 65 Z"/>
                    <path d="M20 38 H12 Q2 38 5 52 Q7 62 20 62"/>
                </svg>
            </div>

            <div class="welcome-cup welcome-cup-right">
                <svg viewBox="0 0 100 100">
                    <path d="M20 30 H70 V65 Q70 80 55 80 H35 Q20 80 20 65 Z"/>
                    <path d="M70 38 H78 Q88 38 85 52 Q83 62 70 62"/>
                </svg>
            </div>

            <span class="welcome-spark">✦</span>

        </div>

        <p class="welcome-text">Every cup, every mark.</p>

    </div>

    <script>
        // 登入成功時播放咖啡廳鈴鐺聲
        const bellSound = new Audio("images/cafe-bell.mp3");

        setTimeout(() => {
            bellSound.volume = 0.5;
            bellSound.play().catch(error => {
                console.log("鈴鐺音效未播放", error);
            });
        }, 480);

        // 約一秒後進入首頁
        setTimeout(() => {
            window.location.replace("index.php");
        }, 1100);

    </script>

</body>
</html>