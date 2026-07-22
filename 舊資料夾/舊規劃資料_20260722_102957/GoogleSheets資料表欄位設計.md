# Google Sheets 資料表欄位設計

建立日期：2026-07-22

請建立一個 Google Sheets：

`游榮吉教育基金會MIS_資料庫`

每一個工作表的第一列請使用以下欄位名稱。

## 1. 年度

```text
year_id
year_name
roc_year
start_date
end_date
status
notes
created_at
updated_at
```

範例：

```text
Y2026
115年度
115
2026/01/01
2026/12/31
進行中
```

## 2. 學校

```text
school_id
school_name
school_short_name
school_type
city
district
address
phone
website
contact_person
contact_title
contact_mobile
contact_email
student_count
class_count
school_category
remote_status
notes
status
created_at
updated_at
```

## 3. 講師

```text
teacher_id
teacher_name
mobile
email
specialty
professional_field
hourly_rate
session_rate
transportation_fee
bank_name
bank_branch
bank_account
account_name
notes
status
created_at
updated_at
```

提醒：銀行資料屬敏感資料，若 AppSheet 權限尚未設定好，可先不要放入第一版。

## 4. 專案

```text
project_id
year_id
project_name
project_code
project_type
school_id
main_teacher_id
project_manager
start_date
end_date
budget_amount
actual_amount
participant_target
participant_actual
status
description
notes
created_at
updated_at
```

專案類型建議：

```text
深耕教育
公益倡議
營隊
教育論壇
國際交流
校園參訪
藝文活動
安全教育
其他
```

## 5. 課程

```text
course_id
project_id
school_id
teacher_id
course_name
semester
total_weeks
hours_per_session
participant_count
start_date
end_date
teaching_goal
description
status
created_at
updated_at
```

## 6. 課程日誌

```text
session_id
course_id
project_id
school_id
teacher_id
session_number
session_date
start_time
end_time
student_count
teaching_content
learning_status
teacher_note
admin_note
photo_folder
status
created_by
created_at
updated_at
```

手機填寫時最重要欄位：

1. 課程
2. 日期
3. 第幾週
4. 學生人數
5. 教學內容
6. 學習狀況
7. 上傳照片

## 7. 活動

```text
activity_id
year_id
project_id
school_id
activity_name
activity_type
activity_date
start_time
end_time
location
city
district
organizer
contact_person
student_count
teacher_count
volunteer_count
guest_count
total_participants
budget_amount
actual_amount
description
photo_folder
status
created_at
updated_at
```

活動類型建議：

```text
F16
創意立體拼圖
行動美術館
AI體驗
安全教育
劇場參訪
成果展
始業式
結業式
教育論壇
國際交流
校園參訪
營隊
其他
```

## 8. 收支

```text
transaction_id
year_id
project_id
activity_id
transaction_date
transaction_type
category
amount
vendor_or_payer
payment_method
invoice_number
receipt_file
reimbursement_status
description
notes
created_at
updated_at
```

`transaction_type`：

```text
收入
支出
```

收入類別：

```text
捐款
補助
專案收入
活動收入
利息
其他
```

支出類別：

```text
講師費
交通費
教材費
教具費
場地費
印刷費
餐費
住宿費
活動費
行政費
資訊費
設備費
其他
```

核銷狀態：

```text
待核銷
已核銷
不需核銷
退回
```

## 9. 成果附件

```text
attachment_id
year_id
project_id
activity_id
course_id
session_id
attachment_type
title
description
file_url
drive_folder
created_by
created_at
```

附件類型：

```text
照片
影片
簽到表
收據
合約
成果報告
新聞稿
媒體報導
其他
```

## 10. 選項設定

```text
option_group
option_value
sort_order
is_active
notes
```

可放入：

1. 專案類型
2. 活動類型
3. 支出類別
4. 收入類別
5. 專案狀態
6. 核銷狀態

## ID 命名建議

若先手動建立，可用以下格式：

```text
學校：SCH-0001
講師：TCH-0001
專案：PRJ-2026-0001
課程：CRS-2026-0001
課程日誌：SES-2026-0001
活動：ACT-2026-0001
收支：TRX-2026-0001
附件：ATT-2026-0001
```

日後可再用 AppSheet 或 Apps Script 自動產生 ID。

