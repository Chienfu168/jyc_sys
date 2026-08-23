<?php
$active = 'system-security';

$statusLabels = [
    \App\Domain\Security\SecurityAudit::PASS => '通過',
    \App\Domain\Security\SecurityAudit::WARN => '注意',
    \App\Domain\Security\SecurityAudit::FAIL => '危險',
];
$statusColors = [
    \App\Domain\Security\SecurityAudit::PASS => '#1b7a43',
    \App\Domain\Security\SecurityAudit::WARN => '#9a6a00',
    \App\Domain\Security\SecurityAudit::FAIL => '#b32d2d',
];

ob_start();
?>
<section class="stats-grid">
    <div class="stat-card">
        <span>通過</span>
        <strong style="color:<?= e($statusColors['pass']) ?>"><?= e((string) $summary['pass']) ?></strong>
    </div>
    <div class="stat-card">
        <span>注意</span>
        <strong style="color:<?= e($statusColors['warn']) ?>"><?= e((string) $summary['warn']) ?></strong>
    </div>
    <div class="stat-card">
        <span>危險</span>
        <strong style="color:<?= e($statusColors['fail']) ?>"><?= e((string) $summary['fail']) ?></strong>
    </div>
    <div class="stat-card">
        <span>可疑檔案</span>
        <strong style="color:<?= e(count($flaggedFiles) ? $statusColors['fail'] : $statusColors['pass']) ?>"><?= e((string) count($flaggedFiles)) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>系統安全檢查</h2>
            <p class="muted-text">檢查環境設定與檔案保護狀態。此頁僅做檢查,不會變更任何設定。</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th style="width:180px">檢查項目</th>
                <th style="width:90px">狀態</th>
                <th>說明 / 建議</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($checks as $check): ?>
                <tr>
                    <td><?= e($check['label']) ?></td>
                    <td><strong style="color:<?= e($statusColors[$check['status']] ?? '#333') ?>"><?= e($statusLabels[$check['status']] ?? $check['status']) ?></strong></td>
                    <td>
                        <?= e($check['detail']) ?>
                        <?php if (!empty($check['recommendation'])): ?>
                            <div class="muted-text"><?= e($check['recommendation']) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>上傳檔案安全檢查</h2>
            <p class="muted-text">
                掃描上傳目錄(<?= e(implode('、', $scannedDirectories)) ?>),列出需特別處理的可疑檔案。
            </p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>檔案路徑</th>
                <th style="width:320px">問題說明</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($flaggedFiles as $file): ?>
                <tr>
                    <td class="mono">storage/<?= e($file['path']) ?></td>
                    <td><strong style="color:<?= e($statusColors['fail']) ?>"><?= e($file['reason']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$flaggedFiles): ?>
                <tr><td colspan="2" class="empty-state">未發現可疑上傳檔案。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($flaggedFiles): ?>
        <p class="muted-text" style="padding:0 4px">
            建議由管理員登入主機,確認上述檔案來源後移除;系統為安全起見不提供直接刪除功能。
        </p>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
