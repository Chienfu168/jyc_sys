# 基金會管理系統

PHP + MySQL 內部管理系統，採模組化架構設計。

## 目前完成範圍

- 登入 / 登出
- 忘記密碼與重設密碼 token 流程
- 登入失敗次數限制
- CSRF 防護
- 使用者管理
- 角色與權限基礎
- 儀表板
- 操作紀錄
- GitHub Release 檢查與更新包下載
- MySQL schema
- 預留捐款人、捐款紀錄、活動、志工、文件模組資料表

## 建議環境

- PHP 8.2+
- MySQL 8 或 MariaDB 10.6+
- Apache 或 Nginx
- PHP extensions：PDO、pdo_mysql、zip
- Laragon 或 XAMPP 可作為本機開發環境

## 安裝步驟

cPanel 租用主機請優先看：

[docs/cPanel安裝部署.md](docs/cPanel安裝部署.md)

若已將檔案上傳到主機，可直接開啟：

```text
https://你的網域/install.php
```

網頁安裝程序會依序完成環境檢查、`.env` 設定、資料庫匯入、管理員建立與安裝鎖定。

1. 複製環境設定：

```bash
copy .env.example .env
```

2. 修改 `.env` 內的資料庫連線。

如需啟用 GitHub 更新檢查，另設定：

```env
APP_VERSION=0.1.0
GITHUB_REPO=your-org/foundation-system
GITHUB_TOKEN=
UPDATE_CHANNEL=stable
```

公開 repo 可不填 `GITHUB_TOKEN`。私有 repo 建議使用只具備讀取 release 權限的 token。

3. 匯入資料庫：

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seeds/initial.sql
```

如果是既有資料庫，要加上系統更新模組資料表：

```bash
mysql -u root -p < database/migrations/20260722_103001_create_system_update_logs.sql
```

4. 建立第一個系統管理員：

```bash
php database/seed_admin.php admin@example.com StrongPassword123
```

5. 啟動本機開發伺服器：

```bash
php -S localhost:8000 -t public public/index.php
```

6. 開啟：

```text
http://localhost:8000
```

## 忘記密碼

目前預設 `MAIL_ENABLED=false`。在本機開發環境送出忘記密碼後，重設連結會寫入：

```text
storage/logs/password-reset.log
```

正式環境應接上 SMTP 或內部 Email 服務。

## GitHub 系統更新

後台「系統更新」支援 GitHub Release 線上一鍵更新：

- 檢查 GitHub Releases 最新版本
- 比對 `.env` 的 `APP_VERSION`
- 下載 zip 更新包到 `storage/updates`
- 備份目前程式到 `storage/backups`
- 備份目前資料庫到 `storage/backups`
- 解壓並驗證更新包
- 套用 `app`、`config`、`database`、`docs`、`public`、`resources` 等系統檔案
- 執行尚未套用的 SQL migration
- 更新 `.env` 的 `APP_VERSION`
- 記錄 sha256 與操作紀錄

更新不會覆蓋 `.env`、`storage`、`.git`、上傳檔案與備份檔。套用失敗時系統會嘗試還原程式備份；資料庫備份會保留供人工還原。

### 建立 GitHub Release

線上更新讀取的是 GitHub Release，不是單純的 commit。每次要提供線上更新時，請到 GitHub 建立新版 Release：

1. 進入 `https://github.com/Chienfu168/jyc_sys`
2. 點右側或上方的 `Releases`
3. 點 `Draft a new release`
4. 建立新 tag，例如 `v0.1.1`
5. Release title 可填 `v0.1.1`
6. 按 `Publish release`
7. 將主機 `.env` 的 `APP_VERSION` 維持在目前已安裝版本，例如 `0.1.0`
8. 後台「系統更新」按 `檢查更新`

如果畫面出現 `GitHub 找不到 Latest Release`，通常代表尚未建立 Release，或私有 repo 的 `GITHUB_TOKEN` 權限不足。

如果下載更新包出現 `HTTP 415`，代表主機上的舊版更新模組送出的 GitHub zipball request header 不相容。請先手動覆蓋最新版 `app/Modules/SystemUpdate/Services/GithubReleaseService.php`，或手動上傳最新版系統檔案一次，之後再使用後台線上更新。

## 重要部署設定

正式環境網站根目錄應指向 `public`，不要指向專案根目錄。

cPanel 若無法把網站根目錄指到 `public`，請使用 [cPanel 安裝部署說明](docs/cPanel安裝部署.md) 的 `public_html` 分離方式。

不可讓瀏覽器直接存取：

- `app`
- `config`
- `database`
- `storage`
- `.env`
