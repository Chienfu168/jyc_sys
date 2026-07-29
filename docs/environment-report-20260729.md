# Environment Report 2026-07-29

檢查時間：2026-07-29

## 本機結果

已可用：

- Git：`C:\Program Files\Git\cmd\git.exe`
- 專案內建 PHP CLI：`.tools/php-8.4.23/php.exe`
- PHP 版本：8.4.23
- PHP extensions：PDO、pdo_mysql、mbstring、openssl、curl、zip、json、session 皆已載入

未在 Windows PATH 找到：

- `php`
- `composer`
- `mysql`
- `gh`
- `node`
- `npm`

## 判斷

目前開發必要條件足夠，因為專案內建 PHP CLI 可直接執行語法檢查。

後續若要更順，建議補強：

- 將 `.tools/php-8.4.23` 加入 Windows PATH，讓 `php` 可直接使用
- 安裝 Composer，方便未來加入 PDF、Excel、郵件等套件
- 安裝 MySQL CLI，方便本機匯入匯出資料庫
- GitHub CLI 非必要，因為 GitHub Actions 已可自動建立 Release
