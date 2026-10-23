<?php

// 讀取環境設定
$env = parse_ini_file(__DIR__ . "/.env", false, INI_SCANNER_RAW);

if ($env === false) {
    throw new RuntimeException("無法讀取環境設定");
}

// 取得資料庫連線資訊
$host = $env["DB_HOST"];
$dbname = $env["DB_NAME"];
$user = $env["DB_USER"];
$password = $env["DB_PASSWORD"];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("資料庫連線失敗！");
}