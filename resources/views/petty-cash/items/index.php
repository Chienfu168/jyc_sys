<?php
$active = 'petty-cash';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <h2>零用金常用項目</h2>
        <div class="actions">
            <a class="btn" href="/petty-cash">返回零用金</a>
            <a class="btn primary" href="/petty-cash-items/create">新增常用項目</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>類型</th>
                <th>項目名稱</th>
                <th class="amount">預設金額</th>
                <th>排序</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['item_type'] === 'income' ? '收入' : '支出') ?></td>
                    <td><?= e($item['name']) ?></td>
                    <td class="amount"><?= e($item['default_amount'] === null ? '-' : number_format((float) $item['default_amount'], 0)) ?></td>
                    <td><?= e((string) $item['sort_order']) ?></td>
                    <td><span class="badge <?= $item['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($item['status'] === 'active' ? '啟用' : '停用') ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/petty-cash-items/<?= e((string) $item['id']) ?>/edit">編輯</a>
                        <form method="post" action="/petty-cash-items/<?= e((string) $item['id']) ?>/toggle">
                            <?= csrf_field() ?>
                            <button class="btn small ghost" type="submit"><?= $item['status'] === 'active' ? '停用' : '啟用' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="6" class="empty">目前沒有常用項目</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
