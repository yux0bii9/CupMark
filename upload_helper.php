<?php

// 共用圖片上傳函式
function uploadImage($image) {

        // 確認圖片是否上傳成功
        if ($image["error"] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("圖片上傳失敗");
        }

        // 限制圖片大小為 5MB
        if ($image["size"] > 5 * 1024 * 1024) {
            throw new RuntimeException("圖片不可超過 5MB");
        }

    // 允許上傳的圖片格式
    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    // 取得檔案的實際 MIME 類型
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($image["tmp_name"]);

    // 檢查圖片格式是否符合規定
    if (!isset($allowedTypes[$mimeType])) {
        throw new RuntimeException("只允許 JPG、PNG、WebP 圖片");
    }

    // 產生不容易重複的隨機檔名
    $image_name = bin2hex(random_bytes(16))
        . "." . $allowedTypes[$mimeType];

    // 設定圖片儲存位置
    $image_path = "uploads/" . $image_name;

    // 將圖片移動到 uploads 資料夾
    if (!move_uploaded_file(
        $image["tmp_name"],
        __DIR__ . "/" . $image_path
    )) {
        throw new RuntimeException("圖片儲存失敗");
    }

    // 回傳圖片路徑
    return $image_path;

}