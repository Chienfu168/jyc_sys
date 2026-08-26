<?php
$active = 'calendar';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>連結外部日曆</h2>
            <p class="muted-text">貼上 Google 等公開日曆的 iCal（.ics）訂閱網址，事件會唯讀顯示於行事曆。可連結多個。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/calendar">返回行事曆</a>
            <?php if ($feeds): ?>
                <form method="post" action="/calendar-feeds/sync-all">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">全部同步</button>
                </form>
            <?php endif; ?>
            <a class="btn primary" href="/calendar-feeds/create">新增外部日曆</a>
        </div>
    </div>

    <details class="panel-hint">
        <summary>如何取得 Google 日曆的 iCal 網址？</summary>
        <ol class="muted-text">
            <li>電腦版開啟 Google 日曆 → 左側「我的日曆」對該日曆點「⋮」→ 設定和共用。</li>
            <li>若要公開：於「存取權限」勾選「公開此日曆」。</li>
            <li>捲到「整合日曆」，複製「iCal 格式的公開網址」（結尾為 <code>.ics</code>）。</li>
            <li>回到這裡「新增外部日曆」貼上該網址即可。</li>
        </ol>
    </details>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>名稱</th>
                <th>顏色</th>
                <th>iCal 網址</th>
                <th>狀態</th>
                <th>最後同步</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($feeds as $feed): ?>
                <tr>
                    <td><strong><?= e($feed['name']) ?></strong></td>
                    <td><span class="calendar-legend-dot" style="background: <?= e($feed['color']) ?>;"></span></td>
                    <td class="mono" style="max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= e($feed['ics_url']) ?></td>
                    <td><span class="badge <?= $feed['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($feed['status'] === 'active' ? '啟用' : '停用') ?></span></td>
                    <td>
                        <?= e($feed['last_synced_at'] ? substr((string) $feed['last_synced_at'], 0, 16) : '尚未同步') ?>
                        <?php if (!empty($feed['last_error'])): ?>
                            <div style="color:#b00020; font-size:12px;">錯誤：<?= e($feed['last_error']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <form method="post" action="/calendar-feeds/<?= e((string) $feed['id']) ?>/sync">
                            <?= csrf_field() ?>
                            <button class="btn small" type="submit">同步</button>
                        </form>
                        <a class="btn small" href="/calendar-feeds/<?= e((string) $feed['id']) ?>/edit">編輯</a>
                        <form method="post" action="/calendar-feeds/<?= e((string) $feed['id']) ?>/toggle">
                            <?= csrf_field() ?>
                            <button class="btn small ghost" type="submit"><?= $feed['status'] === 'active' ? '停用' : '啟用' ?></button>
                        </form>
                        <form method="post" action="/calendar-feeds/<?= e((string) $feed['id']) ?>/delete" onsubmit="return confirm('確定要刪除此外部日曆連結？');">
                            <?= csrf_field() ?>
                            <button class="btn small danger" type="submit">刪除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$feeds): ?>
                <tr><td colspan="6" class="empty">尚未連結任何外部日曆。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
