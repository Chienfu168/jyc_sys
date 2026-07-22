# 游榮吉教育基金會智慧營運管理系統

本專案規劃為「非營利教育基金會營運管理系統」，核心目標是把基金會的人事、學校、講師、專案、深耕教育、活動紀錄、財務、年度預算、工作計畫與成果報表整合為一套可追溯的 Web-Based MIS。

## 系統定位

系統核心資料主軸：

`年度 -> 工作計畫 / 預算 -> 專案 -> 學校 / 講師 / 課程 / 活動 -> 財務 -> 成果 -> 年度報告`

第一階段先完成營運管理與報表基礎，不直接取代正式會計軟體。

## 建議技術

1. Laravel 13
2. Filament 5 Admin Panel
3. Livewire 4
4. MySQL
5. Tailwind CSS
6. Spatie Laravel Permission

## 第一階段 MVP

1. 登入與權限
2. Dashboard
3. 人事與使用者角色
4. 學校管理
5. 學校聯絡人
6. 講師管理
7. 專案管理
8. 深耕教育課程
9. 課程日誌
10. 活動紀錄
11. 預算管理
12. 支出管理
13. 附件管理
14. 基礎統計報表

## 目前已建立的基礎檔案

1. `開發紀錄.md`：需求與開發紀錄
2. `docs/architecture.md`：系統架構設計
3. `docs/implementation-plan.md`：開發順序
4. `database/migrations/2026_07_22_000001_create_foundation_core_tables.php`：核心資料表 migration 草稿
5. `database/schema.sql`：MySQL schema 草稿
6. `app/Models`：核心 Model 關聯草稿
7. `composer.json`：Laravel/Filament 依賴設定草稿
8. `.env.example`：環境設定範例
9. `config/foundation.php`：角色、專案類型、活動類型、收支類別設定
10. `database/seeders/FoundationSetupSeeder.php`：基礎角色 seeder
11. `Google精簡版系統規格.md`：Google Workspace 精簡版規格
12. `GoogleSheets資料表欄位設計.md`：Google Sheets 欄位設計
13. `AppSheet建置操作手冊.md`：AppSheet 建置步驟
14. `Google方案建置檢查清單.md`：實作檢查清單

## 後續安裝指令

本機需先安裝 PHP、Composer、Node.js、MySQL。

完成環境後建議執行：

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=FoundationSetupSeeder
```

Filament 與權限套件安裝完成後，再建立 Panel、Resources、Widgets 與 Seed Data。

版本依據：Laravel 13 官方支援 PHP 8.3+，Filament 5 官方支援 PHP 8.2+、Laravel 11.28+、Livewire 4+；本專案新建議直接以 PHP 8.3+、Laravel 13、Filament 5 為起點。
