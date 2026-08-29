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
            <a class="btn" href="/board-meetings/<?= e((string) $meeting['id']) ?>/print?type=signin">列印簽到表</a>
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

    <?php if (!empty($meeting['chair_remarks'])): ?>
        <h3 class="purchase-print-section-title">主席致詞</h3>
        <p><?= nl2br(e($meeting['chair_remarks'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($meeting['report_items'])): ?>
        <h3 class="purchase-print-section-title">報告事項</h3>
        <p><?= nl2br(e($meeting['report_items'])) ?></p>
    <?php endif; ?>

    <h3 class="purchase-print-section-title">討論事項</h3>
    <?php foreach ($agendaItems as $index => $item): ?>
        <div class="board-meeting-line">
            <strong>案由<?= e((string) ($index + 1)) ?>：</strong><?= nl2br(e($item['subject'])) ?>
            <?php if (!empty($item['explanation'])): ?><div class="muted-text"><strong>說明：</strong><?= nl2br(e($item['explanation'])) ?></div><?php endif; ?>
            <?php if (!empty($item['proposal'])): ?><div class="muted-text"><strong>擬辦：</strong><?= nl2br(e($item['proposal'])) ?></div><?php endif; ?>
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

    <?php if (!empty($meeting['attachments'])): ?>
        <h3 class="purchase-print-section-title">附件</h3>
        <p><?= nl2br(e($meeting['attachments'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($meeting['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($meeting['notes'])) ?></p>
    <?php endif; ?>
</section>

<?php
$files = $files ?? [];
$attachmentFiles = array_values(array_filter($files, static fn (array $f): bool => $f['category'] === 'attachment'));
$signinFiles = array_values(array_filter($files, static fn (array $f): bool => $f['category'] === 'signin_sheet'));
$renderFileRow = static function (array $f) use ($meeting, $canManage): void {
    $sizeKb = number_format(((int) ($f['file_size'] ?? 0)) / 1024, 1);
    ?>
    <tr>
        <td><a href="/board-meetings/<?= e((string) $meeting['id']) ?>/files/<?= e((string) $f['id']) ?>"><?= e($f['title'] ?: $f['original_name']) ?></a></td>
        <td class="muted-text"><?= e($f['original_name']) ?></td>
        <td class="muted-text"><?= e($sizeKb) ?> KB</td>
        <td>
            <?php if ($canManage): ?>
                <form method="post" action="/board-meetings/<?= e((string) $meeting['id']) ?>/files/<?= e((string) $f['id']) ?>/delete" onsubmit="return confirm('確定要刪除此檔案？');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php
};
?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>附件檔案</h2>
            <p class="muted-text">上傳議程／會議紀錄之附件，列印時依序附於文件之後（圖片檔可自動內嵌）。</p>
        </div>
    </div>
    <table class="table">
        <thead><tr><th>標題</th><th>原始檔名</th><th>大小</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($attachmentFiles as $f): ?>
            <?php $renderFileRow($f); ?>
        <?php endforeach; ?>
        <?php if (!$attachmentFiles): ?>
            <tr><td colspan="4" class="empty">尚未上傳附件。</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php if ($canManage): ?>
        <form method="post" action="/board-meetings/<?= e((string) $meeting['id']) ?>/files" enctype="multipart/form-data" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="category" value="attachment">
            <div class="grid-form">
                <label>
                    <span>附件標題（可對應「附件一、二…」）</span>
                    <input type="text" name="title" maxlength="160" placeholder="例如：113年度工作計畫">
                </label>
                <label>
                    <span>選擇檔案（PDF／圖片，上限 15MB）</span>
                    <input type="file" name="file" required>
                </label>
            </div>
            <div class="form-actions">
                <button class="btn primary" type="submit">上傳附件</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>簽到簿掃描檔</h2>
            <p class="muted-text">會議結束後，上傳簽到簿正本掃描檔留存。</p>
        </div>
    </div>
    <table class="table">
        <thead><tr><th>標題</th><th>原始檔名</th><th>大小</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($signinFiles as $f): ?>
            <?php $renderFileRow($f); ?>
        <?php endforeach; ?>
        <?php if (!$signinFiles): ?>
            <tr><td colspan="4" class="empty">尚未上傳簽到簿掃描檔。</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php if ($canManage): ?>
        <form method="post" action="/board-meetings/<?= e((string) $meeting['id']) ?>/files" enctype="multipart/form-data" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="category" value="signin_sheet">
            <div class="grid-form">
                <label>
                    <span>標題</span>
                    <input type="text" name="title" maxlength="160" placeholder="例如：簽到簿掃描檔">
                </label>
                <label>
                    <span>選擇檔案（PDF／圖片，上限 15MB）</span>
                    <input type="file" name="file" required>
                </label>
            </div>
            <div class="form-actions">
                <button class="btn primary" type="submit">上傳簽到簿掃描檔</button>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
