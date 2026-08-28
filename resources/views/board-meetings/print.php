<?php
$active = 'board-meetings';
$documentTitle = $type === 'agenda' ? '董事會議議程' : '董事會議紀錄';
$sessionTitle = \App\Domain\BoardMeetings\MeetingLabel::sessionTitle((int) $meeting['term_no'], (int) $meeting['session_no']);
$foundationName = $profile['foundation_name'] ?? foundation_name();
$directors = array_values(array_filter($attendees, static fn (array $a): bool => $a['role'] === 'director'));
$observers = array_values(array_filter($attendees, static fn (array $a): bool => $a['role'] === 'observer'));
$directorNames = implode('、', array_map(static fn (array $a): string => $a['name'] . ($a['attendance_status'] === 'leave' ? '(請假)' : ($a['attendance_status'] === 'proxy' ? '(委託出席)' : '')), $directors));
ob_start();
?>
<section class="panel no-print">
    <div class="panel-header">
        <div>
            <p class="eyebrow"><?= e($documentTitle) ?></p>
            <h2><?= e($sessionTitle) ?></h2>
        </div>
        <div class="actions">
            <a class="btn" href="/board-meetings/<?= e((string) $meeting['id']) ?>">返回明細</a>
            <a class="btn" href="?type=agenda">議程版</a>
            <a class="btn" href="?type=minutes">會議紀錄版</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>
</section>

<article class="board-meeting-print">
    <h2 class="bm-doc-title"><?= e($documentTitle) ?></h2>
    <h3 class="bm-doc-subtitle"><?= e($foundationName . $sessionTitle) ?></h3>
    <h3 class="bm-doc-subtitle">會議<?= $type === 'agenda' ? '議程' : '紀錄' ?></h3>

    <?php
    $bmReport = trim((string) ($meeting['report_items'] ?? ''));
    $bmRemarks = trim((string) ($meeting['chair_remarks'] ?? ''));
    $bmAttachments = trim((string) ($meeting['attachments'] ?? ''));
    $bmExtempore = trim((string) ($meeting['extempore_motions'] ?? ''));
    ?>
    <ol class="bm-body">
        <li>會議日期：<?= e(roc_date($meeting['meeting_date'])) ?><?= $meeting['meeting_time'] !== null && $meeting['meeting_time'] !== '' ? '　' . e($meeting['meeting_time']) : '' ?></li>
        <li>會議地點：<?= e($meeting['location'] ?: '　　　　') ?></li>
        <li>出席董事：<?= $type === 'agenda' ? '全體董事' : ($directorNames !== '' ? e($directorNames) : '詳見簽到簿') ?></li>
        <?php if ($observers): ?>
            <li>列席人員：<?= e(implode('、', array_column($observers, 'name'))) ?></li>
        <?php endif; ?>
        <li>主　席：<?= e($meeting['chairperson'] ?: '　　　　') ?>　　　　紀　錄：<?= e($meeting['recorder'] ?: '　　　　') ?></li>
        <?php if ($type === 'agenda' && $bmRemarks !== ''): ?>
            <li>主席致詞：<div class="bm-multiline"><?= nl2br(e($bmRemarks)) ?></div></li>
        <?php endif; ?>
        <li>報告事項：<?= $bmReport !== '' ? '<div class="bm-multiline">' . nl2br(e($bmReport)) . '</div>' : '無' ?></li>
        <li>
            討論事項：
            <?php if ($agendaItems): ?>
                <?php foreach ($agendaItems as $index => $item): ?>
                    <?php
                    $bmExplain = trim((string) ($item['explanation'] ?? ''));
                    $bmProposal = trim((string) ($item['proposal'] ?? ''));
                    ?>
                    <div class="bm-agenda-item">
                        <p>案由<?= e(board_meeting_case_no($index + 1)) ?>：<?= nl2br(e($item['subject'])) ?></p>
                        <?php if ($bmExplain !== ''): ?><p class="bm-agenda-sub">說　明：<?= nl2br(e($bmExplain)) ?></p><?php endif; ?>
                        <?php if ($bmProposal !== ''): ?><p class="bm-agenda-sub">擬　辦：<?= nl2br(e($bmProposal)) ?></p><?php endif; ?>
                        <?php if ($type === 'minutes'): ?><p class="bm-agenda-sub">決　議：<?= trim((string) ($item['resolution'] ?? '')) !== '' ? nl2br(e($item['resolution'])) : '照案通過' ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?= $type === 'agenda' ? '（尚未擬定討論案由）' : '無' ?></p>
            <?php endif; ?>
        </li>
        <?php if ($type === 'minutes' && $bmAttachments !== ''): ?>
            <li>附　件：<div class="bm-multiline"><?= nl2br(e($bmAttachments)) ?></div></li>
        <?php endif; ?>
        <?php if ($type === 'minutes'): ?>
            <li>臨時動議：<?= $bmExtempore !== '' ? nl2br(e($bmExtempore)) : '略' ?></li>
            <li>散會。</li>
        <?php endif; ?>
    </ol>

    <?php if ($type === 'minutes'): ?>
        <div class="bm-signatures">
            <div class="bm-signature-line">紀錄：<span class="bm-signature-space"></span></div>
            <div class="bm-signature-line">主席：<span class="bm-signature-space"></span></div>
        </div>
    <?php endif; ?>
</article>

<?php if ($type === 'minutes'): ?>
    <article class="board-meeting-print bm-signin-sheet">
        <h2 class="bm-doc-title">簽到表</h2>
        <h3 class="bm-doc-subtitle"><?= e($foundationName . $sessionTitle) ?></h3>
        <table class="table">
            <thead><tr><th style="width:60px">序</th><th>姓名</th><th>身分</th><th>簽名</th></tr></thead>
            <tbody>
            <?php foreach ($attendees as $index => $attendee): ?>
                <tr>
                    <td><?= e((string) ($index + 1)) ?></td>
                    <td><?= e($attendee['name']) ?></td>
                    <td><?= e(\App\Domain\BoardMeetings\MeetingLabel::roleLabel((string) $attendee['role'])) ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$attendees): ?>
                <tr><td colspan="4" class="empty">尚未登錄出列席人員。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </article>
<?php endif; ?>
<?php
function board_meeting_case_no(int $n): string
{
    $digits = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
    if ($n <= 0) {
        return (string) $n;
    }
    if ($n < 10) {
        return $digits[$n];
    }
    if ($n < 20) {
        return '十' . ($n % 10 === 0 ? '' : $digits[$n % 10]);
    }
    if ($n < 100) {
        return $digits[intdiv($n, 10)] . '十' . ($n % 10 === 0 ? '' : $digits[$n % 10]);
    }
    return (string) $n;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
