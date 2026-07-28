<?php
$active = 'system-update';
ob_start();
?>
<section class="stats-grid update-summary">
    <div class="stat-card">
        <span>目前版本</span>
        <strong><?= e($version) ?></strong>
    </div>
    <div class="stat-card">
        <span>GitHub Repo</span>
        <strong class="text-fit"><?= e($repo ?: '未設定') ?></strong>
    </div>
    <div class="stat-card">
        <span>更新通道</span>
        <strong><?= e(config('app.update_channel', 'stable')) ?></strong>
    </div>
    <div class="stat-card">
        <span>模式</span>
        <strong>一鍵套用</strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>GitHub Release 檢查</h2>
        <form method="post" action="/system-update/check">
            <?= csrf_field() ?>
            <button class="btn primary" type="submit">檢查更新</button>
        </form>
    </div>

    <?php if (!$repo): ?>
        <div class="alert error">請先在 .env 設定 GITHUB_REPO，例如 your-org/foundation-system。</div>
    <?php endif; ?>

    <?php if ($release): ?>
        <div class="release-box">
            <div>
                <span class="muted-text">最新版本</span>
                <h3><?= e($release['name'] ?: $release['tag_name']) ?></h3>
                <p><?= e($release['published_at']) ?></p>
                <?php if ($release['html_url']): ?>
                    <a class="text-link" href="<?= e($release['html_url']) ?>" target="_blank" rel="noopener">查看 GitHub Release</a>
                <?php endif; ?>
            </div>
            <div class="release-actions">
                <?php if ($release['is_newer']): ?>
                    <span class="badge ok">可更新</span>
                    <form method="post" action="/system-update/download">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">下載更新包</button>
                    </form>
                <?php else: ?>
                    <span class="badge muted">目前無新版</span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($release['body']): ?>
            <details class="release-notes">
                <summary>版本說明</summary>
                <pre><?= e($release['body']) ?></pre>
            </details>
        <?php endif; ?>
    <?php else: ?>
        <p class="muted-text">按下「檢查更新」後，系統會連線 GitHub Releases 取得最新版本資訊。</p>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>套用更新</h2>
        <?php if ($latestPackage): ?>
            <form method="post" action="/system-update/apply" onsubmit="return confirm('系統會先備份程式與資料庫，再套用已下載更新包。確定要繼續？');">
                <?= csrf_field() ?>
                <button class="btn primary" type="submit">套用已下載更新包</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($latestPackage): ?>
        <div class="release-box">
            <div>
                <span class="muted-text">可套用版本</span>
                <h3><?= e($latestPackage['version_to'] ?? '-') ?></h3>
                <p><?= e($latestPackage['package_path'] ?? '-') ?></p>
                <p class="mono"><?= e($latestPackage['package_sha256'] ?? '') ?></p>
            </div>
            <div class="release-actions">
                <span class="badge ok">已下載</span>
            </div>
        </div>
    <?php else: ?>
        <p class="muted-text">尚無已下載的更新包。請先檢查更新並下載 GitHub Release zip。</p>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>安全更新流程</h2>
    </div>
    <ol class="steps">
        <li class="done">檢查 GitHub Release</li>
        <li class="done">下載 zip 更新包到 storage/updates</li>
        <li class="done">備份目前程式與資料庫</li>
        <li class="done">解壓至暫存目錄並驗證檔案</li>
        <li class="done">切換維護模式</li>
        <li class="done">覆蓋程式並執行 migration</li>
        <li class="done">失敗時嘗試還原程式備份</li>
    </ol>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>更新紀錄</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>時間</th>
                <th>動作</th>
                <th>狀態</th>
                <th>版本</th>
                <th>檔案</th>
                <th>人員</th>
                <th>訊息</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['created_at']) ?></td>
                    <td><?= e($log['action']) ?></td>
                    <td>
                        <span class="badge <?= $log['status'] === 'success' ? 'ok' : 'muted' ?>">
                            <?= e($log['status']) ?>
                        </span>
                    </td>
                    <td><?= e(($log['version_from'] ?? '-') . ' → ' . ($log['version_to'] ?? '-')) ?></td>
                    <td><?= e($log['package_path'] ?? '-') ?></td>
                    <td><?= e($log['user_name'] ?? '系統') ?></td>
                    <td><?= e($log['message'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="7" class="empty">尚無更新紀錄</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
