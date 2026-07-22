# AppSheet 建置操作手冊

建立日期：2026-07-22

## 1. 建立 Google Drive 資料夾

在 Google Drive 建立：

```text
游榮吉教育基金會MIS
```

底下建立：

```text
00_系統資料表
01_學校資料
02_講師資料
03_專案資料
04_深耕課程
05_活動照片
06_財務憑證
07_成果附件
08_年度報表
```

## 2. 建立 Google Sheets

在 `00_系統資料表` 建立：

```text
游榮吉教育基金會MIS_資料庫
```

依照 `GoogleSheets資料表欄位設計.md` 建立工作表與欄位。

## 3. 建立 AppSheet App

1. 開啟 AppSheet
2. 使用基金會 Google Workspace 帳號登入
3. 選擇 Create App
4. 選擇 Start with existing data
5. 選擇 Google Sheets
6. 選擇 `游榮吉教育基金會MIS_資料庫`
7. 讓 AppSheet 自動建立初始 App

## 4. 檢查資料表

進入 AppSheet 編輯器後：

1. 到 Data
2. 確認所有工作表都有被加入
3. 確認每張表的 Key 欄位

建議 Key 欄位：

```text
年度：year_id
學校：school_id
講師：teacher_id
專案：project_id
課程：course_id
課程日誌：session_id
活動：activity_id
收支：transaction_id
成果附件：attachment_id
```

## 5. 設定欄位型態

常用型態：

```text
日期：Date
時間：Time
金額：Price 或 Decimal
人數：Number
說明：LongText
狀態：Enum
附件：File
照片：Image
關聯欄位：Ref
```

## 6. 設定資料關聯

請將以下欄位設定為 Ref：

```text
專案.year_id -> 年度
專案.school_id -> 學校
專案.main_teacher_id -> 講師
課程.project_id -> 專案
課程.school_id -> 學校
課程.teacher_id -> 講師
課程日誌.course_id -> 課程
課程日誌.project_id -> 專案
課程日誌.school_id -> 學校
課程日誌.teacher_id -> 講師
活動.year_id -> 年度
活動.project_id -> 專案
活動.school_id -> 學校
收支.year_id -> 年度
收支.project_id -> 專案
收支.activity_id -> 活動
成果附件.project_id -> 專案
成果附件.activity_id -> 活動
成果附件.course_id -> 課程
成果附件.session_id -> 課程日誌
```

## 7. 設定主要畫面

建議 Bottom Navigation 放：

1. 首頁
2. 專案
3. 課程日誌
4. 活動
5. 收支

其他頁面放 Menu：

1. 學校
2. 講師
3. 成果附件
4. 年度
5. 選項設定

## 8. 手機填寫優先畫面

課程日誌表單請優先顯示：

1. 課程
2. 日期
3. 第幾週
4. 學生人數
5. 教學內容
6. 學習狀況
7. 教師備註
8. 照片或附件

活動紀錄表單請優先顯示：

1. 活動名稱
2. 活動日期
3. 地點
4. 學生人數
5. 教師人數
6. 志工人數
7. 來賓人數
8. 活動說明
9. 照片資料夾

收支表單請優先顯示：

1. 日期
2. 收入或支出
3. 專案
4. 類別
5. 金額
6. 對象
7. 收據或發票附件
8. 核銷狀態

## 9. 權限設定

若第一階段只有內部人員使用：

1. AppSheet Security 先要求登入
2. Users 設定允許的基金會帳號
3. 不開放 Public App
4. 不允許匿名填寫

後續若要開放講師填寫，再新增講師角色與 Security Filter。

## 10. 年度統計

第一版可以先用 Google Sheets 樞紐分析表建立：

1. 年度合作學校數
2. 年度專案數
3. 年度活動數
4. 年度課程堂數
5. 年度服務人次
6. 年度收入
7. 年度支出
8. 各專案支出

日後可再接 Looker Studio 做較美觀的 Dashboard。

## 11. 建置順序建議

1. 先建立 Google Drive 資料夾
2. 建立 Google Sheets 欄位
3. 先輸入 1 個年度
4. 輸入 1 間學校
5. 輸入 1 位講師
6. 建立 1 個專案
7. 建立 1 門課程
8. 建立 1 筆課程日誌
9. 建立 1 筆活動
10. 建立 1 筆支出
11. 建立 1 筆成果附件
12. 再匯入其他資料

## 12. 測試案例

請用以下案例測試：

```text
年度：115年度
學校：坪林國小
專案：AI STEAM 邏輯程式智慧機器人
課程週數：16週
支出：講師費、交通費、教具費
成果：課程照片、成果報告
```

確認 AppSheet 可以：

1. 從學校看到相關專案
2. 從專案看到課程、活動、支出、成果
3. 從課程看到每週課程日誌
4. 從年度統計看到專案數、課程堂數、服務人次與支出金額

