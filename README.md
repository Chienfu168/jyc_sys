# 基金會管理系統

PHP + MySQL 的內部管理系統，目標是部署在一般租用主機 / cPanel 環境。

## 目前功能

- 登入、登出、忘記密碼與重設密碼
- CSRF 防護、登入嘗試限制、操作紀錄
- 使用者管理、角色權限
- 總儀表板
- 年度預算編寫、檢視、編輯與核定
- 財務會計入口：會計系統、零用金、收支紀錄、講師支出費用、出差費用、薪資管理
- 零用金管理：收支紀錄、常用項目選用、常用項目新增與編輯、範例資料
- 零用金報表：年度 / 月份、精簡 / 詳細、項目比例、小計與總計
- 帳號基本資料與登入驗證碼
- 人事活動入口：工作計畫、人事管理、人事請假、活動管理、專案管理、講師管理、志工管理、行事曆管理
- 專案、活動與行事曆串接：活動可歸屬專案，並自動同步行事曆事件
- 活動報名、簽到名冊與成果紀錄
- 主要功能頁支援列印，並可透過瀏覽器列印視窗另存 PDF
- GitHub Release 線上更新
- cPanel 安裝程序 `install.php`

## 環境需求

- PHP 8.2+
- MySQL 8 或 MariaDB 10.6+
- PHP extensions：PDO、pdo_mysql、zip
- Apache / Nginx / cPanel

## 安裝

1. 上傳系統檔案到主機網站目錄。
2. 建立 MySQL 資料庫與使用者。
3. 複製 `.env.example` 為 `.env`，並填入資料庫與網站設定。
4. 開啟：

```text
https://你的網域/install.php
```

5. 依畫面完成資料庫匯入、migration 與管理員建立。

## 線上更新設定

`.env` 主要設定：

```env
APP_VERSION=0.3.8
GITHUB_REPO=Chienfu168/jyc_sys
GITHUB_TOKEN=
UPDATE_CHANNEL=stable
```

公開 repo 可先不填 `GITHUB_TOKEN`；私有 repo 需要 GitHub Personal Access Token，至少要能讀取 repository 與 release。

## Release 流程

Push 到 `main` 後，GitHub Actions 會依 `.env.example` 的 `APP_VERSION` 自動建立對應 tag / release。每次要讓線上更新抓到新版本時，請先提高 `APP_VERSION`。
