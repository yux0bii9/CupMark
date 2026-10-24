# ☕ CUPMARK

### Every cup, every mark.

**記錄每一杯咖啡，也收藏每一次探索。**

CUPMARK 是一個以咖啡廳探索與分享為主題的 Web Application。

從搜尋咖啡廳、分享照片與心得，到解鎖隱藏成就，
我希望讓每一次咖啡廳體驗，都能成為值得留下的紀錄。

這是我從零開始規劃與開發的第一個完整 Web 專案，
也是我從會計領域跨入軟體開發的重要起點。

---

## 🌐 Explore CUPMARK

**[☕ Visit CUPMARK](https://cupmark.great-site.net/index.php)**

> Demo 目前部署於 InfinityFree 免費主機，
> 首次載入、頁面切換與貼文發布可能需要較長時間。
> 大家可以配上一杯咖啡、喝上一口，稍微等待一下！

---

## 🛠 Tech Stack｜技術架構

- **Frontend：** HTML5、CSS3、JavaScript
- **Backend：** PHP
- **Database：** MariaDB、SQL
- **Database Connection：** PDO
- **External API：** Google Maps Places API
- **Development Environment：** XAMPP、VS Code
- **Version Control：** Git、GitHub
- **Deployment：** InfinityFree

### How CUPMARK Works｜系統運作流程

CUPMARK 使用 PHP 處理伺服器端邏輯，
透過 PDO 與 MariaDB 進行資料存取，
並使用 JavaScript 實作前端互動及 Google Maps API 整合。

**主要資料流程：**

`User Interaction → JavaScript / HTML Form → PHP → PDO → MariaDB`

依照不同功能，PHP 負責處理登入驗證、
貼文資料操作及回應結果，再由前端呈現內容。

---

## ✨ Features｜功能介紹

### 🔐 Member System｜會員註冊與登入

- 會員註冊、登入及登出
- PHP Session 登入狀態管理
- 使用者暱稱與個人資訊顯示

### 📍 Café Discovery｜Google Maps 咖啡廳搜尋

- 整合 Google Maps Places API
- 搜尋並選擇咖啡廳地標
- 顯示地標資訊與 Google Maps 連結

### 📸 Café Journal｜咖啡廳分享紀錄

- 建立咖啡廳分享貼文
- 上傳 1–2 張照片
- 編輯貼文內容、地標與照片
- 刪除個人貼文
- 依發布時間顯示最新貼文

### 🏆 Hidden Achievements｜隱藏成就系統

- 根據使用者活動解鎖成就
- 透過不同的探索與分享行為累積紀錄
- 保留隱藏條件，增加使用者探索的樂趣

### 📝 Daily Coffee Notes｜今日咖啡筆記

- 每日顯示咖啡相關小知識
- 為網站加入探索咖啡文化的小驚喜

---

## 🔐 Security & Data Handling｜安全性與資料處理

- 使用 PHP Session 管理登入狀態
- 使用 PDO 進行資料庫操作
- 使用 CSRF Token 保護相關操作
- 驗證上傳圖片與使用者輸入
- 透過 `.env` 管理環境設定
- 不將真實資料庫密碼與環境設定提交至 Git

---

## 🚀 Local Setup｜本機環境設定

### Requirements｜環境需求

- PHP
- MariaDB / MySQL
- Apache（例如 XAMPP）
- Google Maps API Key

### Installation｜安裝步驟

1. Clone this repository.
2. 將專案放入 XAMPP 的 `htdocs` 資料夾。
3. 建立 MariaDB 資料庫並匯入專案所需資料表。
4. 複製 `.env.example`，重新命名為 `.env`。
5. 在 `.env` 中設定資料庫連線資訊與 Google Maps API Key。
6. 啟動 Apache 與 MySQL。
7. 在瀏覽器開啟專案網址。

---

## 📌 Project Background｜專案背景

CUPMARK 是我從會計領域轉向軟體開發的第一個完整 Web 專案。

我熱愛穿梭在不同國家、城市的咖啡廳，
品嚐咖啡、感受每間店獨有的氛圍。
因此，我選擇以咖啡廳紀錄作為第一個開發專案的起點。

在開發過程中，我從基礎 PHP、SQL 與 JavaScript 開始學習，
逐步實作使用者驗證、資料庫 CRUD、第三方 API 整合、
圖片上傳、前後端互動與網站部署。

透過這個專案，我希望持續累積實際開發經驗，
並加深對 Web Application Architecture 與資料庫設計的理解。

---

## 🔮 Future Improvements｜未來規劃

- [ ] 優化響應式介面與使用者體驗
- [ ] 改善資料庫查詢與網站效能
- [ ] CUPMARK 2.0：新增地區探索功能
- [ ] CUPMARK 2.0：新增留言互動功能
- [ ] 持續強化安全性與錯誤處理
- [ ] 重構程式碼結構，提高維護性

---

### ☕ CUPMARK — Every cup, every mark.