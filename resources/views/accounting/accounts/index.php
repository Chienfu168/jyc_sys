<?php
$active = 'accounting';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>會計科目表</h2>
            <p class="muted-text">以非營利組織常用分類建立，後續傳票、總帳與報表都會依此彙整。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/accounting">返回會計系統</a>
            <?php if (\App\Core\Permission::can('accounting.manage')): ?>
                <a class="btn primary" href="/accounting/accounts/create">新增科目</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>代碼</th>
                <th>科目名稱</th>
                <th>類型</th>
                <th>上層科目</th>
                <th>借貸方向</th>
                <th>說明</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr>
                    <td class="mono"><?= e($account['code']) ?></td>
                    <td><strong><?= e($account['name']) ?></strong></td>
                    <td><?= e(accounting_account_type_label($account['account_type'])) ?></td>
                    <td><?= e($account['parent_code'] ? $account['parent_code'] . ' ' . $account['parent_name'] : '-') ?></td>
                    <td><?= e($account['normal_balance'] === 'debit' ? '借方' : '貸方') ?></td>
                    <td><?= e($account['description'] ?: '-') ?></td>
                    <td><span class="badge <?= $account['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($account['status'] === 'active' ? '啟用' : '停用') ?></span></td>
                    <td class="actions">
                        <?php if (\App\Core\Permission::can('accounting.manage')): ?>
                            <a class="btn small" href="/accounting/accounts/<?= e((string) $account['id']) ?>/edit">編輯</a>
                            <form method="post" action="/accounting/accounts/<?= e((string) $account['id']) ?>/toggle">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit"><?= e($account['status'] === 'active' ? '停用' : '啟用') ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$accounts): ?>
                <tr><td colspan="8" class="empty">尚未建立會計科目。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function accounting_account_type_label(string $type): string
{
    return [
        'asset' => '資產',
        'liability' => '負債',
        'net_asset' => '淨資產',
        'income' => '收入',
        'expense' => '支出',
    ][$type] ?? $type;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
