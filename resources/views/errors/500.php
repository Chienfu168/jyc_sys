<?php
// 自包含頁面:不套用 layouts/main.php,避免在系統異常(如資料庫故障)時
// layout 內的查詢再度拋出例外造成二次錯誤。
$title = $title ?? '系統發生錯誤';
?><!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <style>
        body { font-family: "Microsoft JhengHei", Arial, sans-serif; background: #f4f5f7; color: #333; margin: 0; }
        .box { max-width: 480px; margin: 12vh auto; background: #fff; border-radius: 8px; padding: 40px; box-shadow: 0 2px 12px rgba(0,0,0,.08); text-align: center; }
        h1 { font-size: 22px; margin: 0 0 12px; }
        p { color: #666; line-height: 1.6; }
        a { display: inline-block; margin-top: 20px; padding: 10px 24px; background: #2c6cb0; color: #fff; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="box">
        <h1><?= e($title) ?></h1>
        <p>系統發生錯誤,請稍後再試。<br>若問題持續發生,請聯絡系統管理員。</p>
        <a href="/">返回首頁</a>
    </div>
</body>
</html>
