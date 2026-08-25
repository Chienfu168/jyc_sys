<?php
$active = 'net-asset-statements';
$canManage = \App\Core\Permission::can('net_asset_statements.manage');
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">主管機關核備</p>
            <h2>淨值變動表</h2>
            <p class="muted-text">獨立手動輸入年度淨值變動表，供陳報主管機關核備；暫不連結實際帳務。</p>
        </div>
        <?php if ($canManage): ?>
            <a class="btn primary" href="/net-asset-statements/create">新增淨值變動表</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>年度</th>
                <th>表冊名稱</th>
                <th>狀態</th>
                <th>建立人</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($statements as $statement): ?>
                <tr>
                    <td><?= e(roc_year_label($statement['fiscal_year'])) ?></td>
                    <td><?= e($statement['title']) ?></td>
                    <td><span class="badge <?= $statement['status'] === 'confirmed' ? 'ok' : 'muted' ?>"><?= $statement['status'] === 'confirmed' ? '已確認' : '草稿' ?></span></td>
                    <td><?= e($statement['created_by_name'] ?: '-') ?></td>
                    <td class="actions">
                        <a class="btn small" href="/net-asset-statements/<?= e((string) $statement['id']) ?>">檢視</a>
                        <?php if ($canManage): ?>
                            <a class="btn small" href="/net-asset-statements/<?= e((string) $statement['id']) ?>/edit">編輯</a>
                            <form method="post" action="/net-asset-statements/<?= e((string) $statement['id']) ?>/delete" onsubmit="return confirm('確定要刪除此淨值變動表？此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$statements): ?>
                <tr><td colspan="5" class="empty">尚未建立淨值變動表。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
