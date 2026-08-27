<?php

namespace App\Modules\Calendar\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Modules\Calendar\Services\CalendarFeedService;
use PDO;

/**
 * 外部日曆訂閱(Google 等公開日曆的 iCal 網址)管理。
 */
final class CalendarFeedController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('calendar.manage');

        $feeds = Database::pdo()->query(
            'SELECT * FROM calendar_feeds ORDER BY status, sort_order, id'
        )->fetchAll();

        $this->render('calendar.feeds.index', [
            'title' => '連結外部日曆',
            'section' => '業務與人事',
            'active' => 'calendar',
            'feeds' => $feeds,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('calendar.manage');

        $this->render('calendar.feeds.create', [
            'title' => '新增外部日曆',
            'section' => '業務與人事',
            'active' => 'calendar',
            'feed' => [
                'name' => '',
                'ics_url' => '',
                'color' => '#4285F4',
                'sort_order' => 10,
                'status' => 'active',
            ],
            'action' => '/calendar-feeds',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('calendar.manage');
        $this->validateFeed('/calendar-feeds/create');

        $params = $this->params();
        $params['created_at'] = now();
        Database::pdo()->prepare(
            'INSERT INTO calendar_feeds (name, ics_url, color, sort_order, status, created_at, updated_at)
             VALUES (:name, :ics_url, :color, :sort_order, :status, :created_at, :updated_at)'
        )->execute($params);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'calendar', 'calendar_feeds', $id);

        // 建立後立即嘗試同步一次,讓事件盡快顯示。
        if (CalendarFeedService::sync($id)) {
            flash('success', '外部日曆已新增並同步成功，事件將顯示於行事曆。');
        } else {
            flash('error', '外部日曆已新增，但同步失敗，請查看清單上的錯誤訊息並確認網址為公開的 iCal（.ics）連結。');
        }
        redirect('/calendar-feeds');
    }

    public function edit(string $id): void
    {
        $this->requirePermission('calendar.manage');

        $this->render('calendar.feeds.edit', [
            'title' => '編輯外部日曆',
            'section' => '業務與人事',
            'active' => 'calendar',
            'feed' => $this->findFeed((int) $id),
            'action' => '/calendar-feeds/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('calendar.manage');
        $this->findFeed((int) $id);
        $this->validateFeed('/calendar-feeds/' . $id . '/edit');

        $params = $this->params();
        $params['id'] = (int) $id;
        Database::pdo()->prepare(
            'UPDATE calendar_feeds
             SET name = :name, ics_url = :ics_url, color = :color,
                 sort_order = :sort_order, status = :status, updated_at = :updated_at
             WHERE id = :id'
        )->execute($params);

        AuditLog::write('update', 'calendar', 'calendar_feeds', (int) $id);
        flash('success', '外部日曆已更新。');
        redirect('/calendar-feeds');
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('calendar.manage');
        $feed = $this->findFeed((int) $id);
        $status = $feed['status'] === 'active' ? 'disabled' : 'active';

        Database::pdo()->prepare('UPDATE calendar_feeds SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute(['status' => $status, 'updated_at' => now(), 'id' => (int) $id]);

        AuditLog::write('toggle_status', 'calendar', 'calendar_feeds', (int) $id, ['status' => $status]);
        flash('success', '外部日曆狀態已更新。');
        redirect('/calendar-feeds');
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('calendar.manage');
        $feed = $this->findFeed((int) $id);

        Database::pdo()->prepare('DELETE FROM calendar_feeds WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'calendar', 'calendar_feeds', (int) $id, ['name' => $feed['name'] ?? null]);
        flash('success', '外部日曆已刪除。');
        redirect('/calendar-feeds');
    }

    public function sync(string $id): void
    {
        $this->requirePermission('calendar.manage');
        $this->findFeed((int) $id);

        $ok = CalendarFeedService::sync((int) $id);
        flash($ok ? 'success' : 'error', $ok ? '已同步外部日曆。' : '同步失敗，請確認網址是否為公開的 iCal 連結。');
        redirect('/calendar-feeds');
    }

    public function syncAll(): void
    {
        $this->requirePermission('calendar.manage');

        $count = CalendarFeedService::syncAllActive();
        flash('success', '已同步 ' . $count . ' 個外部日曆。');
        redirect('/calendar-feeds');
    }

    private function validateFeed(string $path): void
    {
        if ($error = Validator::required($_POST, ['name' => '名稱', 'ics_url' => 'iCal 網址'])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $url = CalendarFeedService::normalizeUrl((string) $_POST['ics_url']);
        if (!preg_match('#^https?://#i', $url)) {
            $this->backWithInput($path, $_POST, 'iCal 網址需以 http://、https:// 或 webcal:// 開頭（Google 日曆的「iCal 格式的公開網址」）。');
        }
    }

    /** @return array<string, mixed> */
    private function params(): array
    {
        $color = trim((string) ($_POST['color'] ?? ''));
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#4285F4';
        }

        return [
            'name' => trim((string) $_POST['name']),
            'ics_url' => CalendarFeedService::normalizeUrl((string) $_POST['ics_url']),
            'color' => $color,
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
            'status' => ($_POST['status'] ?? '') === 'disabled' ? 'disabled' : 'active',
            'updated_at' => now(),
        ];
    }

    /** @return array<string, mixed> */
    private function findFeed(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM calendar_feeds WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $feed = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$feed) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到外部日曆']);
            exit;
        }

        return $feed;
    }
}
