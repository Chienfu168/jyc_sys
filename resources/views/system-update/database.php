<?php
$active = 'system-update-db';
ob_start();
?>
<section class="stats-grid update-summary">
    <div class="stat-card">
        <span>目前版本</span>
        <strong><?= e($version) ?></strong>
    </div>
    <div class="stat-card">
        <span>已套用更新</span>
        <strong><?= e((string) $status['appliedCount']) ?> / <?= e((string) $status['total']) ?></strong>
    </div>
    <div class="stat-card">
        <span>待套用更新</span>
        <strong<?= !empty($status['pending']) ? ' style="color:#b00020;"' : '' ?>><?= e((string) count($status['pending'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>資料庫狀態</span>
        <strong><?= empty($status['pending']) ? '最新' : '需更新' ?></strong>
    </div>
</section>

<?php if (!empty($error)): ?>
    <section class="panel">
        <p class="muted-text" style="color:#b00020;">無法讀取資料庫更新狀態：<?= e($error) ?></p>
    </section>
<?php endif; ?>

<section class="panel ops-bar">
    <div>
        <h2>資料庫更新</h2>
        <p class="muted-text">套用尚未執行的資料庫 migration（例如新增欄位、回填編號）。此操作只會執行「待套用」清單中的更新，且已套用者不會重複執行。建議操作前先備份資料庫。</p>
    </div>
    <div class="ops-actions">
        <a class="btn" href="/system-update">返回系統更新</a>
        <?php if (!empty($status['pending'])): ?>
            <form method="post" action="/system-update/migrate" onsubmit="return confirm('確定要套用 <?= e((string) count($status['pending'])) ?> 個待套用的資料庫更新？建議先完成備份。');">
                <?= csrf_field() ?>
                <button class="btn primary" type="submit">套用資料庫更新</button>
            </form>
        <?php else: ?>
            <button class="btn" type="button" disabled>資料庫已是最新</button>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <h3>待套用清單（<?= e((string) count($status['pending'])) ?>）</h3>
    <?php if (!empty($status['pending'])): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th style="width:60px">序</th><th>Migration 檔名</th></tr></thead>
                <tbody>
                <?php foreach ($status['pending'] as $index => $name): ?>
                    <tr>
                        <td><?= e((string) ($index + 1)) ?></td>
                        <td class="mono"><?= e($name) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="muted-text">沒有待套用的資料庫更新，資料庫結構已與程式版本一致。</p>
    <?php endif; ?>
</section>

<section class="panel">
    <details>
        <summary>已套用清單（<?= e((string) $status['appliedCount']) ?>）</summary>
        <div class="table-wrap">
            <table>
                <thead><tr><th style="width:60px">序</th><th>Migration 檔名</th></tr></thead>
                <tbody>
                <?php foreach ($status['applied'] as $index => $name): ?>
                    <tr>
                        <td><?= e((string) ($index + 1)) ?></td>
                        <td class="mono"><?= e($name) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($status['applied'])): ?>
                    <tr><td colspan="2" class="empty">尚無已套用紀錄。</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </details>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
