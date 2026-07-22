<?php

namespace App\Core;

abstract class Controller
{
    protected function render(string $template, array $data = []): void
    {
        view($template, $data);
        unset($_SESSION['_old']);
    }

    protected function requireAuth(): void
    {
        if (!Auth::instance()->check()) {
            redirect('/login');
        }
    }

    protected function requirePermission(string $permission): void
    {
        $this->requireAuth();

        if (!Permission::can($permission)) {
            http_response_code(403);
            view('errors.403', ['title' => '沒有權限']);
            exit;
        }
    }

    protected function backWithInput(string $path, array $input, string $message): never
    {
        $_SESSION['_old'] = $input;
        flash('error', $message);
        redirect($path);
    }
}
