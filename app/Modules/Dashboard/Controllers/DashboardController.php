<?php

namespace App\Modules\Dashboard\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $stats = [
            'users' => (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'roles' => (int) Database::pdo()->query('SELECT COUNT(*) FROM roles')->fetchColumn(),
            'active_users' => (int) Database::pdo()->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn(),
            'logs' => (int) Database::pdo()->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn(),
        ];

        $stmt = Database::pdo()->query(
            'SELECT audit_logs.*, users.name AS user_name
             FROM audit_logs
             LEFT JOIN users ON users.id = audit_logs.user_id
             ORDER BY audit_logs.created_at DESC
             LIMIT 8'
        );

        $this->render('dashboard.index', [
            'title' => '儀表板',
            'stats' => $stats,
            'logs' => $stmt->fetchAll(),
        ]);
    }
}
