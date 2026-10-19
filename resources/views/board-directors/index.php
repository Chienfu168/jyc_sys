<?php
$active = 'board-directors';
$directors = $directors ?? [];
$documentTitle = '董事名冊';
ob_start();
?>
<style>
.directors-table td, .directors-table th { white-space: nowrap; }
.directors-table td.wrap, .directors-table th.wrap { white-space: normal; }
@media print {
    .directors-table { font-size: 12px; }
    .directors-table .no-print { display: none !important; }
}
</style>

<div class="print-only" style="text-align:center;margin-bottom:8px">
    <strong style="font-size:16px"><?= e(foundation_name()) ?> — 董事名冊</strong>
</div>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>董事名冊</h2>
            <p class="muted-text">以董事／監察人為中心的聯絡資料，任期異動時標示卸任即可保留歷任名冊，與各次董事會議的出列席紀錄分開維護。</p>
        </div>
        <div class="actions no-print">
            <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage)): ?>
                <a class="btn primary" href="/board-directors/create">新增董事</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="search bank-filter no-print" method="get" action="/board-directors">
        <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="姓名、服務單位、地址">
        <select name="status">
            <option value="current" <?= $status === 'current' ? 'selected' : '' ?>>現任</option>
            <option value="former" <?= $status === 'former' ? 'selected' : '' ?>>歷任(前任)</option>
            <option value="" <?= $status === '' ? 'selected' : '' ?>>全部</option>
        </select>
        <button class="btn" type="submit">查詢</button>
    </form>

    <div class="table-wrap">
        <table class="directors-table">
            <thead>
            <tr>
                <th>職稱</th>
                <th>姓名</th>
                <th class="wrap">服務單位／職稱</th>
                <th>聯絡方式</th>
                <th class="wrap">地址</th>
                <th>任期</th>
                <th>狀態</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($directors as $d): ?>
                <tr>
                    <td><strong><?= e($d['title'] ?: '-') ?></strong></td>
                    <td><?= e($d['name']) ?></td>
                    <td class="wrap"><?= e(trim(($d['organization'] ?? '') . ($d['job_title'] ? '　' . $d['job_title'] : ''))) ?: '-' ?></td>
                    <td>
                        <?= e($d['phone'] ?: ($d['mobile'] ?: '-')) ?>
                        <?php if (!empty($d['email'])): ?>
                            <div class="muted-text"><?= e($d['email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="wrap"><?= e($d['address'] ?: '-') ?></td>
                    <td><?= e(roc_date($d['started_on'])) ?><?= $d['status'] === 'former' ? ' ～ ' . e(roc_date($d['ended_on'])) : '' ?></td>
                    <td><span class="badge <?= $d['status'] === 'current' ? 'ok' : 'muted' ?>"><?= e($d['status'] === 'current' ? '現任' : '前任') ?></span></td>
                    <td class="actions no-print">
                        <a class="btn small" href="/board-directors/<?= e((string) $d['id']) ?>">檢視</a>
                        <?php if (!empty($canManage)): ?>
                            <a class="btn small" href="/board-directors/<?= e((string) $d['id']) ?>/edit">編輯</a>
                            <form method="post" action="/board-directors/<?= e((string) $d['id']) ?>/delete" onsubmit="return confirm('確定要刪除此董事資料？此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$directors): ?>
                <tr><td colspan="8" class="empty">尚無董事資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="muted-text no-print" style="margin-top:8px">共 <?= e((string) count($directors)) ?> 筆。</p>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
