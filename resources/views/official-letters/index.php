<?php
$active = 'official-letters';
$canManage = \App\Core\Permission::can('official_letters.manage');
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <form class="search" method="get" action="/official-letters">
            <input type="number" name="year" min="1" max="2100" value="<?= e((string) roc_year($year)) ?>">
            <button class="btn" type="submit">查詢</button>
        </form>
        <?php if ($canManage): ?>
            <a class="btn primary" href="/official-letters/create">新增陳報公文</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>發文日期</th>
                <th>發文字號</th>
                <th>受文者</th>
                <th>主旨</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($letters as $letter): ?>
                <tr>
                    <td><?= e(roc_date($letter['letter_date'])) ?></td>
                    <td><?= e($letter['letter_number'] ?: '-') ?></td>
                    <td><?= e($letter['recipient']) ?></td>
                    <td><?= e(mb_strimwidth((string) $letter['subject'], 0, 40, '…')) ?></td>
                    <td><span class="badge <?= $letter['status'] === 'issued' ? 'ok' : 'muted' ?>"><?= $letter['status'] === 'issued' ? '已發文' : '草稿' ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/official-letters/<?= e((string) $letter['id']) ?>">檢視</a>
                        <?php if ($canManage): ?>
                            <a class="btn small" href="/official-letters/<?= e((string) $letter['id']) ?>/edit">編輯</a>
                            <form method="post" action="/official-letters/<?= e((string) $letter['id']) ?>/delete" onsubmit="return confirm('確定要刪除此陳報公文？此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$letters): ?>
                <tr><td colspan="6" class="empty">本年度尚未建立陳報公文。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
