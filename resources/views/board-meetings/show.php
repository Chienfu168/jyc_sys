<?php
$active = 'board-meetings';
$canManage = \App\Core\Permission::can('board_meetings.manage');
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e(\App\Domain\BoardMeetings\MeetingLabel::sessionTitle((int) $meeting['term_no'], (int) $meeting['session_no'])) ?></h2>
            <p class="muted-text"><?= e(roc_date($meeting['meeting_date'])) ?> / <?= e(\App\Domain\BoardMeetings\MeetingLabel::statusLabel((string) $meeting['status'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/board-meetings">返回列表</a>
            <a class="btn" href="/board-meetings/<?= e((string) $meeting['id']) ?>/print?type=agenda">列印議程</a>
            <a class="btn" href="/board-meetings/<?= e((string) $meeting['id']) ?>/print?type=minutes">列印會議紀錄</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/board-meetings/<?= e((string) $meeting['id']) ?>/edit">編輯</a>
                <?php if ($meeting['status'] !== 'confirmed'): ?>
                    <form method="post" action="/board-meetings/<?= e((string) $meeting['id']) ?>/confirm">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">確認為會議紀錄</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="/board-meetings/<?= e((string) $meeting['id']) ?>/delete" onsubmit="return confirm('確定要刪除此董事會議紀錄？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>會議日期</th>
            <td><?= e(roc_date($meeting['meeting_date'])) ?></td>
            <th>會議時間</th>
            <td><?= e($meeting['meeting_time'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>地點</th>
            <td colspan="3"><?= e($meeting['location'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>主席</th>
            <td><?= e($meeting['chairperson'] ?: '-') ?></td>
            <th>紀錄</th>
            <td><?= e($meeting['recorder'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <h3 class="purchase-print-section-title">出列席人員</h3>
    <table class="table">
        <thead><tr><th>姓名</th><th>身分</th><th>出席狀態</th></tr></thead>
        <tbody>
        <?php foreach ($attendees as $attendee): ?>
            <tr>
                <td><?= e($attendee['name']) ?></td>
                <td><?= e(\App\Domain\BoardMeetings\MeetingLabel::roleLabel((string) $attendee['role'])) ?></td>
                <td><?= e(\App\Domain\BoardMeetings\MeetingLabel::attendanceStatusLabel((string) $attendee['attendance_status'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$attendees): ?>
            <tr><td colspan="3" class="empty">尚未登錄出列席人員。</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($meeting['report_items'])): ?>
        <h3 class="purchase-print-section-title">報告事項</h3>
        <p><?= nl2br(e($meeting['report_items'])) ?></p>
    <?php endif; ?>

    <h3 class="purchase-print-section-title">討論事項</h3>
    <?php foreach ($agendaItems as $index => $item): ?>
        <div class="board-meeting-line">
            <strong>案由<?= e((string) ($index + 1)) ?>：</strong><?= nl2br(e($item['subject'])) ?>
            <div class="muted-text"><strong>決議：</strong><?= $item['resolution'] !== '' ? nl2br(e($item['resolution'])) : '（尚未填寫）' ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (!$agendaItems): ?>
        <p class="muted-text">尚無討論案由。</p>
    <?php endif; ?>

    <?php if (!empty($meeting['extempore_motions'])): ?>
        <h3 class="purchase-print-section-title">臨時動議</h3>
        <p><?= nl2br(e($meeting['extempore_motions'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($meeting['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($meeting['notes'])) ?></p>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
