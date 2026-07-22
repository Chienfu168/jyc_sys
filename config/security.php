<?php

return [
    'session_name' => env('SESSION_NAME', 'foundation_session'),
    'session_lifetime_minutes' => (int) env('SESSION_LIFETIME_MINUTES', 60),
    'password_reset_minutes' => 30,
    'max_login_attempts' => 5,
    'login_lock_minutes' => 15,
];
