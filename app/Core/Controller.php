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

    /** 目前登入者的使用者編號(未登入為 0)。 */
    protected function currentUserId(): int
    {
        return (int) (auth()->user()['id'] ?? 0);
    }

    /** 目前登入者是否為該筆資料的建立者。 */
    protected function ownsRecord(int|string|null $ownerId): bool
    {
        $uid = $this->currentUserId();
        return $uid > 0 && (int) $ownerId === $uid;
    }

    /**
     * 需具備指定管理權限,或為該筆資料的建立者本人。
     * 用於「資料建立者可編輯／刪除自己建立的資料」,權限不足且非本人時回傳 403。
     */
    protected function requireManageOrOwner(string $permission, int|string|null $ownerId): void
    {
        $this->requireAuth();

        if (Permission::can($permission) || $this->ownsRecord($ownerId)) {
            return;
        }

        http_response_code(403);
        view('errors.403', ['title' => '沒有權限']);
        exit;
    }

    protected function backWithInput(string $path, array $input, string $message): never
    {
        $_SESSION['_old'] = $input;
        flash('error', $message);
        redirect($path);
    }
}
