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
- Laragon 或 XAMPP 可作為本機開發環境

## 安裝步驟

1. 複製環境設定：

```bash
copy .env.example .env
```

2. 修改 `.env` 內的資料庫連線。

後台「系統更新」會從 GitHub Releases 檢查線上版本。正式環境請設定：

```env
APP_VERSION=0.1.0
GITHUB_REPO=Chienfu168/jyc_sys
GITHUB_TOKEN=
UPDATE_CHANNEL=stable
```

`jyc_sys` 目前是私有 repo，因此正式環境建議設定只具備讀取 repository / release 權限的 `GITHUB_TOKEN`。若日後改成公開 repo，可不填 token。

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

## 後台線上更新

後台「系統更新」目前採半自動安全流程：

- 檢查 GitHub Releases 最新版本
- 比對 `.env` 的 `APP_VERSION`
- 下載 zip 更新包到 `storage/updates`
- 記錄 sha256 與操作紀錄

發布新版時，在 GitHub 建立 Release，tag 使用語意化版本，例如：

```text
v0.1.1
```

後台會以 tag 版本和 `.env` 的 `APP_VERSION` 比對，版本較新時顯示「可更新」並允許下載更新包。

目前尚未自動覆蓋正式程式與執行 migration。下一階段應加入備份、解壓驗證、維護模式、檔案切換與 rollback 後，再開放真正一鍵套用更新。

## 重要部署設定

正式環境網站根目錄應指向 `public`，不要指向專案根目錄。

不可讓瀏覽器直接存取：

- `app`
- `config`
- `database`
- `storage`
- `.env`
