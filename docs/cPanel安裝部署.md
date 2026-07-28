# cPanel 安裝部署說明

本系統是 PHP + MySQL，不綁定 Laragon 或 XAMPP。Laragon/XAMPP 只適合本機測試；租用主機請用 cPanel 的檔案管理員、MySQL 資料庫與 phpMyAdmin 部署。

## 一、主機需求

- PHP 8.2 或以上
- MySQL 8 或 MariaDB 10.6 或以上
- Apache 並支援 `.htaccess`
- PHP extension：PDO、pdo_mysql、zip
- 可設定資料庫使用者與密碼

## 二、建議部署方式

### 方式 A：可設定網站根目錄時

這是最建議的方式。

將整個專案上傳到：

```text
/home/帳號/foundation-system
```

然後在 cPanel 的 Domain / Subdomain / Addon Domain 設定中，將 Document Root 指到：

```text
/home/帳號/foundation-system/public
```

這樣瀏覽器只會看到 `public`，不會直接看到 `.env`、`app`、`config`、`database`、`storage`。

### 方式 B：主網域只能使用 public_html 時

如果主機商不讓你把網站根目錄改成 `public`，請用分離方式：

私密程式放：

```text
/home/帳號/foundation-system
```

公開檔案放：

```text
/home/帳號/public_html
```

操作方式：

1. 將 `app`、`config`、`database`、`resources`、`storage`、`.env`、`composer.json` 放到 `/home/帳號/foundation-system`
2. 將 `public` 目錄裡面的檔案放到 `/home/帳號/public_html`
3. 修改 `/home/帳號/public_html/index.php` 與 `/home/帳號/public_html/install.php`

將：

```php
$basePath = $_SERVER['APP_BASE_PATH'] ?? dirname(__DIR__);
```

改成：

```php
$basePath = dirname(__DIR__) . '/foundation-system';
```

## 三、建立資料庫

在 cPanel：

1. 開啟 MySQL Databases
2. 建立資料庫，例如 `cpaneluser_foundation`
3. 建立資料庫使用者
4. 設定使用者密碼
5. 將使用者加入資料庫，權限勾選 ALL PRIVILEGES

注意：cPanel 常會自動加上帳號前綴，例如：

```text
資料庫：cpaneluser_foundation
使用者：cpaneluser_foundation_user
```

## 四、設定 .env

複製 `.env.example` 為 `.env`，並修改：

```env
APP_NAME="基金會管理系統"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://你的網域
APP_TIMEZONE=Asia/Taipei

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_foundation
DB_USERNAME=cpaneluser_foundation_user
DB_PASSWORD=你的資料庫密碼
```

如果要使用 GitHub 更新檢查：

```env
APP_VERSION=0.1.2
GITHUB_REPO=your-org/foundation-system
GITHUB_TOKEN=
UPDATE_CHANNEL=stable
```

私有 GitHub repo 才需要 `GITHUB_TOKEN`。

## 五、匯入資料庫

建議使用網頁安裝程序：

```text
https://你的網域/install.php
```

安裝程序會依序完成：

1. 環境檢查
2. 寫入 `.env`
3. 匯入資料表與初始權限
4. 建立第一個系統管理員
5. 建立 `storage/installed.lock` 鎖定檔

若要手動匯入，可使用下列方式。

在 cPanel 開啟 phpMyAdmin：

1. 選擇剛建立的資料庫
2. 匯入 `database/schema.sql`
3. 再匯入 `database/seeds/initial.sql`

如果已安裝過舊版，只要新增系統更新模組，匯入：

```text
database/migrations/20260722_103001_create_system_update_logs.sql
```

## 六、建立管理員帳號

如果使用 `install.php`，此步驟會在網頁安裝程序中完成。

如果 cPanel 有 Terminal，可以執行：

```bash
php database/seed_admin.php admin@example.com StrongPassword123
```

如果沒有 Terminal，可先請主機商開啟 Terminal，或之後補一個一次性網頁安裝精靈。管理員密碼必須至少 10 個字元。

## 七、檔案權限

建議：

```text
資料夾：755
檔案：644
storage：755 或主機要求的可寫權限
.env：600 或 640
```

`storage/logs`、`storage/cache`、`storage/private_uploads`、`storage/updates` 必須讓 PHP 可寫入。

## 八、正式環境安全檢查

- `APP_DEBUG=false`
- 網站使用 HTTPS
- `.env` 不可被瀏覽器下載
- `app`、`config`、`database`、`storage` 不可被瀏覽器瀏覽
- 網站根目錄優先指向 `public`
- MySQL 使用者只給本系統資料庫權限
- 定期備份資料庫與 `storage`

## 九、線上更新

後台「系統更新」可串接 GitHub Release：

1. 在 `.env` 設定 `GITHUB_REPO`
2. 私有 repo 另設定 `GITHUB_TOKEN`
3. 到後台「系統更新」檢查版本
4. 下載更新包
5. 按「套用已下載更新包」

套用更新時會自動：

- 建立程式備份到 `storage/backups`
- 建立資料庫備份到 `storage/backups`
- 建立 `storage/maintenance.lock` 進入維護模式
- 解壓並驗證 GitHub zip
- 覆蓋系統程式檔
- 執行尚未套用的 SQL migration
- 更新 `.env` 的 `APP_VERSION`

線上更新需要 PHP `zip` extension，也就是 `ZipArchive`。如果 cPanel 沒開啟，請在 Select PHP Version / PHP Extensions 啟用 `zip`。

如果檢查更新出現 HTTP 404，通常是 GitHub repo 尚未建立 Release。請到 GitHub repo 的 `Releases` 建立新版，例如 `v0.1.1`，再回到系統檢查更新。

如果下載更新包出現 HTTP 415，請先手動更新最新版 `app/Modules/SystemUpdate/Services/GithubReleaseService.php`。這通常是舊版更新模組的 GitHub zipball request header 不相容。
