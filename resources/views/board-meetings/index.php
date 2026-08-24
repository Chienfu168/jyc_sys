<?php
$active = 'board-meetings';
$canManage = \App\Core\Permission::can('board_meetings.manage');
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/board-meetings">
            <select name="status">
                <option value="">全部狀態</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿(議程)</option>
                <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>已確認紀錄</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <?php if ($canManage): ?>
                <a class="btn primary" href="/board-meetings/create">新增董事會議</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>屆次</th>
                    <th>會議日期</th>
                    <th>地點</th>
                    <th>主席</th>
                    <th>狀態</th>
                    <th class="actions">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meetings as $meeting): ?>
                    <tr>
                        <td><?= e(\App\Domain\BoardMeetings\MeetingLabel::sessionTitle((int) $meeting['term_no'], (int) $meeting['session_no'])) ?></td>
                        <td><?= e(roc_date($meeting['meeting_date'])) ?></td>
                        <td><?= e($meeting['location'] ?: '-') ?></td>
                        <td><?= e($meeting['chairperson'] ?: '-') ?></td>
                        <td><span class="badge <?= $meeting['status'] === 'confirmed' ? 'ok' : 'muted' ?>"><?= e(\App\Domain\BoardMeetings\MeetingLabel::statusLabel((string) $meeting['status'])) ?></span></td>
                        <td class="actions">
                            <a class="btn small" href="/board-meetings/<?= e((string) $meeting['id']) ?>">檢視</a>
                            <?php if ($canManage): ?>
                                <a class="btn small" href="/board-meetings/<?= e((string) $meeting['id']) ?>/edit">編輯</a>
                                <form method="post" action="/board-meetings/<?= e((string) $meeting['id']) ?>/delete" onsubmit="return confirm('確定要刪除此董事會議紀錄？此操作無法復原。');">
                                    <?= csrf_field() ?>
                                    <button class="btn small" type="submit">刪除</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$meetings): ?>
                    <tr><td colspan="6" class="empty">查無董事會議資料。</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
