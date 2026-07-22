<?php

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function verify(): void
    {
        $token = $_POST['_token'] ?? '';
        if (!hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
            http_response_code(419);
            view('errors.419', ['title' => '表單已失效']);
            exit;
        }
    }
}
