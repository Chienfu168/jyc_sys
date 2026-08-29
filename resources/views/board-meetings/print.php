<?php
$active = 'board-meetings';
$documentTitle = $type === 'agenda' ? '董事會議議程' : ($type === 'signin' ? '董事會議簽到表' : '董事會議紀錄');
$sessionTitle = \App\Domain\BoardMeetings\MeetingLabel::sessionTitle((int) $meeting['term_no'], (int) $meeting['session_no']);
$foundationName = $profile['foundation_name'] ?? foundation_name();
$directors = array_values(array_filter($attendees, static fn (array $a): bool => $a['role'] === 'director'));
$observers = array_values(array_filter($attendees, static fn (array $a): bool => $a['role'] === 'observer'));
$directorNames = implode('、', array_map(static fn (array $a): string => $a['name'] . ($a['attendance_status'] === 'leave' ? '(請假)' : ($a['attendance_status'] === 'proxy' ? '(委託出席)' : '')), $directors));

$files = $files ?? [];
$attachmentFiles = array_values(array_filter($files, static fn (array $f): bool => $f['category'] === 'attachment'));
$attachmentImages = array_values(array_filter($attachmentFiles, static fn (array $f): bool => str_starts_with((string) ($f['mime_type'] ?? ''), 'image/')));
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
            <a class="btn" href="?type=signin">簽到表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>
</section>

<?php if ($type === 'signin'): ?>
    <article class="board-meeting-print bm-signin-sheet">
        <h2 class="bm-doc-title">簽到表</h2>
        <h3 class="bm-doc-subtitle"><?= e($foundationName . $sessionTitle) ?></h3>
        <div class="bm-signin-meta">
            <span>會議日期：<?= e(roc_date($meeting['meeting_date'])) ?><?= $meeting['meeting_time'] ? '　' . e($meeting['meeting_time']) : '' ?></span>
            <span>會議地點：<?= e($meeting['location'] ?: '　　　　') ?></span>
        </div>
        <table class="table bm-signin-table">
            <thead><tr><th style="width:56px">序</th><th style="width:160px">姓名</th><th style="width:110px">身分</th><th>簽名</th></tr></thead>
            <tbody>
            <?php foreach ($attendees as $index => $attendee): ?>
                <tr>
                    <td><?= e((string) ($index + 1)) ?></td>
                    <td><?= e($attendee['name']) ?></td>
                    <td><?= e(\App\Domain\BoardMeetings\MeetingLabel::roleLabel((string) $attendee['role'])) ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
            <?php for ($i = count($attendees); $i < max(count($attendees) + 3, 8); $i++): ?>
                <tr><td><?= e((string) ($i + 1)) ?></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </article>
<?php else: ?>
    <?php
    $bmReport = trim((string) ($meeting['report_items'] ?? ''));
    $bmRemarks = trim((string) ($meeting['chair_remarks'] ?? ''));
    $bmAttachments = trim((string) ($meeting['attachments'] ?? ''));
    $bmExtempore = trim((string) ($meeting['extempore_motions'] ?? ''));
    $hasAttachments = $bmAttachments !== '' || $attachmentFiles !== [];
    ?>
    <article class="board-meeting-print">
        <h2 class="bm-doc-title"><?= e($documentTitle) ?></h2>
        <h3 class="bm-doc-subtitle"><?= e($foundationName . $sessionTitle) ?></h3>
        <h3 class="bm-doc-subtitle">會議<?= $type === 'agenda' ? '議程' : '紀錄' ?></h3>

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
            <li>臨時動議：<?= $bmExtempore !== '' ? '<div class="bm-multiline">' . nl2br(e($bmExtempore)) . '</div>' : ($type === 'agenda' ? '（如有臨時動議，於會議中提出）' : '略') ?></li>
            <li>
                附　件：
                <?php if (!$hasAttachments): ?>無<?php else: ?>
                    <div class="bm-multiline">
                        <?php if ($bmAttachments !== ''): ?><?= nl2br(e($bmAttachments)) ?><?php endif; ?>
                        <?php if ($attachmentFiles): ?>
                            <?php if ($bmAttachments !== ''): ?><br><?php endif; ?>
                            <?php foreach ($attachmentFiles as $i => $af): ?>
                                附件<?= e(board_meeting_case_no($i + 1)) ?>：<?= e($af['title'] ?: $af['original_name']) ?><br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </li>
            <li>散　會。</li>
        </ol>

        <?php if ($type === 'minutes'): ?>
            <div class="bm-signatures">
                <div class="bm-signature-line">紀錄：<span class="bm-signature-space"></span></div>
                <div class="bm-signature-line">主席：<span class="bm-signature-space"></span></div>
            </div>
        <?php endif; ?>
    </article>

    <?php foreach ($attachmentImages as $index => $img): ?>
        <?php $dataUri = board_meeting_image_data_uri((string) $img['stored_path']); ?>
        <?php if ($dataUri !== null): ?>
            <article class="board-meeting-print bm-attachment-page">
                <h3 class="bm-attachment-caption">附件<?= e(board_meeting_case_no($index + 1)) ?>：<?= e($img['title'] ?: $img['original_name']) ?></h3>
                <img class="bm-attachment-img" src="<?= e($dataUri) ?>" alt="">
            </article>
        <?php endif; ?>
    <?php endforeach; ?>
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

/** 讀取董事會議上傳的圖片附件為 data: URI,供列印時內嵌於文件之後。 */
function board_meeting_image_data_uri(string $storedPath): ?string
{
    $storedPath = trim($storedPath);
    if ($storedPath === '' || !str_starts_with($storedPath, 'private_uploads/board_meetings/')) {
        return null;
    }
    $full = storage_path($storedPath);
    if (!is_file($full) || !is_readable($full)) {
        return null;
    }
    $mimeByExt = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    if (!isset($mimeByExt[$ext])) {
        return null;
    }
    $data = @file_get_contents($full);
    if ($data === false || $data === '') {
        return null;
    }
    return 'data:' . $mimeByExt[$ext] . ';base64,' . base64_encode($data);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
