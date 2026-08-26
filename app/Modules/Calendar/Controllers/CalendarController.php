<?php

namespace App\Modules\Calendar\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Modules\Calendar\Services\CalendarFeedService;
use DateInterval;
use DateTimeImmutable;
use PDO;

final class CalendarController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('calendar.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $type = in_array(($_GET['type'] ?? ''), ['meeting', 'activity', 'project', 'deadline', 'leave', 'reminder', 'other'], true) ? (string) $_GET['type'] : '';
        $status = in_array(($_GET['status'] ?? ''), ['scheduled', 'done', 'cancelled'], true) ? (string) $_GET['status'] : '';
        $keyword = trim((string) ($_GET['q'] ?? ''));

        $monthStart = $month . '-01';
        $monthEnd = (new DateTimeImmutable($monthStart))->modify('last day of this month')->format('Y-m-d');
        $calendarStart = (new DateTimeImmutable($monthStart))->modify('-' . (int) (new DateTimeImmutable($monthStart))->format('w') . ' days');
        $calendarEnd = $calendarStart->add(new DateInterval('P41D'));

        [$events, $summary] = $this->events($monthStart, $monthEnd, $type, $status, $keyword);

        // 外部(Google 等)公開日曆事件:僅在未套用內部類型/狀態篩選時疊加顯示。
        $externalEvents = [];
        $feeds = [];
        if ($type === '' && $status === '') {
            $externalEvents = $this->externalEvents(
                $calendarStart->format('Y-m-d'),
                $calendarEnd->format('Y-m-d'),
                $keyword
            );
            $feeds = CalendarFeedService::activeFeeds();
        }

        $this->render('calendar.index', [
            'title' => '行事曆管理',
            'section' => '業務與人事',
            'active' => 'calendar',
            'month' => $month,
            'type' => $type,
            'status' => $status,
            'keyword' => $keyword,
            'events' => $events,
            'weeks' => $this->weeks($calendarStart, $calendarEnd, array_merge($events, $externalEvents)),
            'summary' => $summary,
            'feeds' => $feeds,
            'externalCount' => count($externalEvents),
            'prevMonth' => (new DateTimeImmutable($monthStart))->modify('-1 month')->format('Y-m'),
            'nextMonth' => (new DateTimeImmutable($monthStart))->modify('+1 month')->format('Y-m'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('calendar.manage');

        $this->render('calendar.create', [
            'title' => '新增行事曆事件',
            'section' => '業務與人事',
            'active' => 'calendar',
            'event' => $this->blankEvent(),
            'action' => '/calendar',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('calendar.manage');
        $this->validateEvent('/calendar/create');

        Database::pdo()->prepare(
            'INSERT INTO calendar_events
             (title, event_type, starts_at, ends_at, all_day, location, owner_name, reminder_minutes, status, description, source_module, source_id, created_by, created_at, updated_at)
             VALUES
             (:title, :event_type, :starts_at, :ends_at, :all_day, :location, :owner_name, :reminder_minutes, :status, :description, :source_module, :source_id, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'source_module' => null,
            'source_id' => null,
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'calendar', 'calendar_events', $id);
        flash('success', '行事曆事件已建立。');
        redirect('/calendar/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('calendar.view');

        $this->render('calendar.show', [
            'title' => '行事曆事件',
            'section' => '業務與人事',
            'active' => 'calendar',
            'event' => $this->findEvent((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('calendar.manage');

        $this->render('calendar.edit', [
            'title' => '編輯行事曆事件',
            'section' => '業務與人事',
            'active' => 'calendar',
            'event' => $this->findEvent((int) $id),
            'action' => '/calendar/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('calendar.manage');
        $event = $this->findEvent((int) $id);
        $this->validateEvent('/calendar/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE calendar_events
             SET title = :title,
                 event_type = :event_type,
                 starts_at = :starts_at,
                 ends_at = :ends_at,
                 all_day = :all_day,
                 location = :location,
                 owner_name = :owner_name,
                 reminder_minutes = :reminder_minutes,
                 status = :status,
                 description = :description,
                 source_module = :source_module,
                 source_id = :source_id,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'source_module' => $event['source_module'] ?: null,
            'source_id' => $event['source_id'] ?: null,
            'updated_at' => now(),
            'id' => (int) $event['id'],
        ]);

        AuditLog::write('update', 'calendar', 'calendar_events', (int) $id);
        flash('success', '行事曆事件已更新。');
        redirect('/calendar/' . $id);
    }

    public function updateStatus(string $id): void
    {
        $this->requirePermission('calendar.manage');
        $this->findEvent((int) $id);

        $status = in_array(($_POST['status'] ?? ''), ['scheduled', 'done', 'cancelled'], true)
            ? (string) $_POST['status']
            : 'scheduled';

        Database::pdo()->prepare('UPDATE calendar_events SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'status' => $status,
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        AuditLog::write('status', 'calendar', 'calendar_events', (int) $id);
        flash('success', '事件狀態已更新。');
        redirect('/calendar/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('calendar.manage');
        $event = $this->findEvent((int) $id);

        if (!empty($event['source_module'])) {
            flash('error', '此事件由其他模組自動同步產生，請至來源資料刪除或調整狀態。');
            redirect('/calendar/' . $id);
        }

        Database::pdo()->prepare('DELETE FROM calendar_events WHERE id = :id')
            ->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'calendar', 'calendar_events', (int) $id, [
            'title' => $event['title'],
        ]);
        flash('success', '事件已刪除。');
        redirect('/calendar');
    }

    private function events(string $monthStart, string $monthEnd, string $type, string $status, string $keyword): array
    {
        $where = ['starts_at <= :month_end', '(ends_at IS NULL OR ends_at >= :month_start)'];
        $params = [
            'month_start' => $monthStart . ' 00:00:00',
            'month_end' => $monthEnd . ' 23:59:59',
        ];

        if ($type !== '') {
            $where[] = 'event_type = :type';
            $params['type'] = $type;
        }
        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(title LIKE :keyword OR location LIKE :keyword OR owner_name LIKE :keyword OR description LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT calendar_events.*, users.name AS created_by_name
             FROM calendar_events
             LEFT JOIN users ON users.id = calendar_events.created_by
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY starts_at, id'
        );
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        return [$events, $this->summary($events)];
    }

    /**
     * 取得外部日曆事件並套用關鍵字篩選,標準化為與內部事件相容的欄位。
     *
     * @return array<int, array<string, mixed>>
     */
    private function externalEvents(string $rangeStart, string $rangeEnd, string $keyword): array
    {
        $events = CalendarFeedService::eventsInRange($rangeStart, $rangeEnd);

        if ($keyword !== '') {
            $needle = mb_strtolower($keyword);
            $events = array_values(array_filter($events, static function (array $event) use ($needle): bool {
                $haystack = mb_strtolower(($event['title'] ?? '') . ' ' . ($event['location'] ?? '') . ' ' . ($event['description'] ?? ''));
                return mb_strpos($haystack, $needle) !== false;
            }));
        }

        return array_map(static function (array $event): array {
            return $event + [
                'event_type' => 'external',
                'all_day' => $event['all_day'] ? 1 : 0,
                'status' => 'scheduled',
                'owner_name' => $event['feed_name'] ?? '',
                'reminder_minutes' => 0,
                'source_module' => 'calendar_feed',
            ];
        }, $events);
    }

    private function weeks(DateTimeImmutable $calendarStart, DateTimeImmutable $calendarEnd, array $events): array
    {
        $eventsByDate = [];
        foreach ($events as $event) {
            $start = new DateTimeImmutable(substr($event['starts_at'], 0, 10));
            $end = !empty($event['ends_at']) ? new DateTimeImmutable(substr($event['ends_at'], 0, 10)) : $start;
            if ($start < $calendarStart) {
                $start = $calendarStart;
            }
            if ($end > $calendarEnd) {
                $end = $calendarEnd;
            }

            for ($day = $start; $day <= $end; $day = $day->add(new DateInterval('P1D'))) {
                $eventsByDate[$day->format('Y-m-d')][] = $event;
            }
        }

        $weeks = [];
        $week = [];
        for ($day = $calendarStart; $day <= $calendarEnd; $day = $day->add(new DateInterval('P1D'))) {
            $date = $day->format('Y-m-d');
            $week[] = [
                'date' => $date,
                'day' => $day->format('j'),
                'events' => $eventsByDate[$date] ?? [],
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        return $weeks;
    }

    private function validateEvent(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'title' => '事件標題',
            'event_type' => '事件類型',
            'starts_at' => '開始時間',
            'status' => '狀態',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!in_array($_POST['event_type'] ?? '', ['meeting', 'activity', 'project', 'deadline', 'leave', 'reminder', 'other'], true)) {
            $this->backWithInput($path, $_POST, '事件類型不正確。');
        }
        if (!in_array($_POST['status'] ?? '', ['scheduled', 'done', 'cancelled'], true)) {
            $this->backWithInput($path, $_POST, '狀態不正確。');
        }

        if (!$this->datetimeValue('starts_at')) {
            $this->backWithInput($path, $_POST, '開始時間格式不正確。');
        }

        $endsAt = $this->datetimeValue('ends_at');
        if (trim((string) ($_POST['ends_at'] ?? '')) !== '' && !$endsAt) {
            $this->backWithInput($path, $_POST, '結束時間格式不正確。');
        }
        if ($endsAt && $endsAt < $this->datetimeValue('starts_at')) {
            $this->backWithInput($path, $_POST, '結束時間不可早於開始時間。');
        }

        if ($this->intValue('reminder_minutes') < 0) {
            $this->backWithInput($path, $_POST, '提醒分鐘數不可小於 0。');
        }
    }

    private function payload(): array
    {
        return [
            'title' => trim((string) $_POST['title']),
            'event_type' => (string) $_POST['event_type'],
            'starts_at' => $this->datetimeValue('starts_at'),
            'ends_at' => $this->datetimeValue('ends_at'),
            'all_day' => isset($_POST['all_day']) ? 1 : 0,
            'location' => $this->nullableText('location'),
            'owner_name' => $this->nullableText('owner_name'),
            'reminder_minutes' => $this->intValue('reminder_minutes'),
            'status' => (string) $_POST['status'],
            'description' => trim((string) ($_POST['description'] ?? '')),
        ];
    }

    private function findEvent(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT calendar_events.*, users.name AS created_by_name
             FROM calendar_events
             LEFT JOIN users ON users.id = calendar_events.created_by
             WHERE calendar_events.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到行事曆事件']);
            exit;
        }

        return $event;
    }

    private function summary(array $events): array
    {
        $today = date('Y-m-d H:i:s');

        return [
            'total' => count($events),
            'upcoming' => count(array_filter($events, static fn (array $event): bool => $event['status'] === 'scheduled' && $event['starts_at'] >= $today)),
            'done' => count(array_filter($events, static fn (array $event): bool => $event['status'] === 'done')),
        ];
    }

    private function blankEvent(): array
    {
        return [
            'title' => '',
            'event_type' => 'meeting',
            'starts_at' => date('Y-m-d H:i'),
            'ends_at' => '',
            'all_day' => 0,
            'location' => '',
            'owner_name' => auth()->user()['name'] ?? '',
            'reminder_minutes' => '60',
            'status' => 'scheduled',
            'description' => '',
            'source_module' => '',
            'source_id' => '',
        ];
    }

    private function datetimeValue(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            return null;
        }

        return $value . ':00';
    }

    private function nullableText(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    private function intValue(string $key): int
    {
        return max(0, (int) ($_POST[$key] ?? 0));
    }
}
