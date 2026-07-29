# Development Workflow

本文件固定基金會管理系統後續建構流程，避免每次都重新判斷環境、版本、檢查與發布步驟。

## 1. 環境檢查

每次換電腦、重開環境、或發現 `php` 指令不能用時，先執行：

```powershell
.\tools\env-check.ps1
```

若 Windows PowerShell 顯示「已停用指令碼執行」，請改用：

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\env-check.ps1
```

目前專案內已保留可攜版 PHP：

```text
.tools/php-8.4.23/php.exe
```

腳本會優先使用這個 PHP，所以不一定要把 PHP 加入 Windows PATH。

必要項目：

- Git
- PHP CLI 8.2+
- PHP extensions：PDO、pdo_mysql、mbstring、openssl、curl、zip、json、session
- storage 相關資料夾
- `.env.example` 內的 `APP_VERSION`

建議但非必要：

- Composer：未來若加入外部 PHP 套件時需要
- MySQL CLI：方便本機匯入、匯出資料庫
- GitHub CLI：GitHub Actions 已能自動 Release，只有排查 Release 時較方便
- Node.js：目前 PHP/MySQL 系統不需要

## 2. 每次建構固定流程

1. 確認需求屬於哪一個模組。
2. 讀取既有 controller、route、view、migration。
3. 優先沿用現有資料表與版型。
4. 功能完成後升級 `.env.example` 的 `APP_VERSION`。
5. 更新 README 或對應文件。
6. 執行：

```powershell
.\tools\verify.ps1
```

若 PowerShell 執行原則阻擋，請改用：

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\verify.ps1
```

7. 確認 Git 只包含本次系統檔案，不包含私人上傳或 Excel 原始檔。
8. commit。
9. push 到 GitHub。
10. GitHub Actions 依 `APP_VERSION` 自動建立 tag / release。

## 3. 發布前檢查

提交並推送前可執行：

```powershell
.\tools\release-ready.ps1
```

若 PowerShell 執行原則阻擋，請改用：

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\release-ready.ps1
```

這會檢查：

- `APP_VERSION` 是否存在且格式為 `x.y.z`
- Git 工作目錄是否乾淨
- 目前分支

## 4. 線上更新流程

正式主機位於 cPanel 時，建議流程：

1. 本機完成開發。
2. 升級 `APP_VERSION`。
3. 執行 `powershell -ExecutionPolicy Bypass -File .\tools\verify.ps1`。
4. commit 並 push。
5. 等 GitHub Actions 建立 Release。
6. 到系統後台「系統更新」檢查更新。
7. 下載並套用更新。
8. 確認 migration 執行完成。
9. 檢查首頁、登入、該次新增模組。

## 5. 固定注意事項

- 不提交 `.env`。
- 不提交 `storage/private_uploads`。
- 不提交 Excel、PDF、匯入暫存檔，除非明確要作為範例資料。
- 資料表異動要用 migration。
- 已上線資料不可用覆蓋 SQL 方式清空，應用 migration 或匯入腳本。
- 牽涉財務、人事、薪資、銀行資料時，預設新增為草稿或待確認狀態。
- 會計傳票先建立草稿，再由會計人員檢查後過帳。
