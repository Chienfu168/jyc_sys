<?php

declare(strict_types=1);

// 測試用最小啟動檔:定義 BASE_PATH 並交由 Composer autoloader 載入
// helpers(composer.json 的 files 區塊)與 App\ 類別(PSR-4)。
// 不建立資料庫連線,純函式測試可獨立執行。

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
