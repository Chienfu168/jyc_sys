<?php

return [
    'session_name' => env('SESSION_NAME', 'foundation_session'),
    'session_lifetime_minutes' => (int) env('SESSION_LIFETIME_MINUTES', 60),
    'remember_cookie' => env('REMEMBER_COOKIE', 'foundation_remember'),
    'remember_lifetime_days' => (int) env('REMEMBER_LIFETIME_DAYS', 30),
    'password_reset_minutes' => 30,
    'max_login_attempts' => 5,
    'login_lock_minutes' => 15,
    // 緊急停用「僅限台灣 IP」連線管制;設為 true 可在被鎖在門外時強制放行。
    'access_control_disabled' => env_bool('ACCESS_CONTROL_DISABLED', false),
    // 自動封鎖累犯 IP(fail2ban 式):於觀察視窗內登入失敗達門檻即暫時封鎖該來源 IP。
    'ip_autoblock_disabled' => env_bool('IP_AUTOBLOCK_DISABLED', false),
    'ip_autoblock_fail_threshold' => (int) env('IP_AUTOBLOCK_FAIL_THRESHOLD', 20),
    'ip_autoblock_window_minutes' => (int) env('IP_AUTOBLOCK_WINDOW_MINUTES', 15),
    'ip_autoblock_minutes' => (int) env('IP_AUTOBLOCK_MINUTES', 60),
];
