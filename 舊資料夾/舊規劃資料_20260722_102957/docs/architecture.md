# 系統基礎架構

## 1. 架構定位

本系統採 Laravel 13 + Filament 5 建置，第一階段先建立基金會內部行政與專案管理後台。使用者主要透過瀏覽器操作，講師端與現場活動紀錄需支援手機版 RWD。

第一階段不做完整會計總帳系統，而是建立「專案財務、預算、支出、附件、報表」的營運管理層，日後可再和正式會計軟體或會計科目銜接。

## 2. 核心模組

1. 使用者與權限
2. 人事管理
3. 學校管理
4. 學校聯絡人
5. 講師管理
6. 合作單位管理
7. 年度工作計畫
8. 年度預算
9. 專案管理
10. 深耕教育計畫
11. 課程日誌
12. 活動紀錄
13. 財務收入與支出
14. 請購與核銷
15. 成果管理
16. 文件與附件
17. 年度工作報告
18. 統計報表

## 3. 核心資料流

`FiscalYear -> WorkPlan -> Project -> Course / Activity -> Expense / Outcome -> AnnualReport`

學校、講師與合作單位是可被多個年度與專案重複使用的主檔資料。

## 4. 權限架構

建議使用 `spatie/laravel-permission`。

角色初稿：

1. Super Admin
2. 系統管理員
3. 執行長／主管
4. 企劃人員
5. 財務／會計
6. 講師
7. 董事

敏感欄位如銀行帳戶、薪資、核銷憑證、捐款資料，需限制角色存取。

## 5. 資料庫原則

1. 重要主檔支援 Soft Delete
2. 金額使用 `decimal(12, 2)`
3. 年度統一使用 `fiscal_year_id` 關聯
4. 類型資料盡量使用可維護資料表，不寫死在程式中
5. 附件只存檔案資訊，不把檔案本體存入 MySQL
6. 重要操作未來需接 Audit Log
7. 初期固定選項先集中於 `config/foundation.php`，待營運穩定後再視需要改為可由後台維護的資料表

## 6. Filament 後台規劃

第一批 Resource：

1. FiscalYearResource
2. SchoolResource
3. TeacherResource
4. PartnerResource
5. ProjectResource
6. CourseResource
7. CourseSessionResource
8. ActivityResource
9. BudgetResource
10. ExpenseResource
11. AttachmentResource

第一批 Dashboard Widget：

1. 年度合作學校數
2. 執行中專案數
3. 深耕教育課程數
4. 年度活動數
5. 年度總預算
6. 年度支出
7. 預算執行率
8. 待補課程日誌
