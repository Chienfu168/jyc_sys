<?php
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>期初餘額</span>
        <strong><?= e(bank_money($totals['opening'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>本期入帳</span>
        <strong><?= e(bank_money($totals['deposit'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>目前餘額</span>
        <strong><?= e(bank_money($totals['balance'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>基金會銀行帳戶</h2>
            <p class="muted-text">管理基金會所屬銀行帳戶，後續可與零用金、收支紀錄與會計系統勾稽。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/bank-transactions">銀行交易</a>
            <?php if (\App\Core\Permission::can('bank_accounts.manage')): ?>
                <a class="btn primary" href="/bank-accounts/create">新增帳戶</a>
                <a class="btn" href="/bank-transactions/create?type=transfer_to_petty_cash">撥補零用金</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>銀行</th>
                <th>戶名</th>
                <th>帳號</th>
                <th>幣別</th>
                <th class="amount">期初</th>
                <th class="amount">入帳</th>
                <th class="amount">出帳</th>
                <th class="amount">目前餘額</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr>
                    <td>
                        <strong><?= e($account['bank_name']) ?></strong>
                        <?php if (!empty($account['branch_name'])): ?>
                            <div class="muted-text"><?= e($account['branch_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($account['account_name']) ?></td>
                    <td class="mono"><?= e($account['account_no']) ?></td>
                    <td><?= e($account['currency']) ?></td>
                    <td class="amount"><?= e(bank_money($account['opening_balance'])) ?></td>
                    <td class="amount"><?= e(bank_money($account['deposit_total'])) ?></td>
                    <td class="amount"><?= e(bank_money($account['withdrawal_total'])) ?></td>
                    <td class="amount"><strong><?= e(bank_money($account['current_balance'])) ?></strong></td>
                    <td>
                        <span class="badge <?= $account['status'] === 'active' ? 'ok' : 'muted' ?>">
                            <?= e($account['status'] === 'active' ? '啟用' : '停用') ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a class="btn small" href="/bank-transactions?bank_account_id=<?= e((string) $account['id']) ?>">交易</a>
                        <?php if (\App\Core\Permission::can('bank_accounts.manage')): ?>
                            <a class="btn small" href="/bank-accounts/<?= e((string) $account['id']) ?>/edit">編輯</a>
                            <form method="post" action="/bank-accounts/<?= e((string) $account['id']) ?>/toggle">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit"><?= e($account['status'] === 'active' ? '停用' : '啟用') ?></button>
                            </form>
                        <?php endif; ?>
                        <?php if (\App\Core\Permission::can('bank_accounts.delete') || owns_record($account['created_by'] ?? null)): ?>
                            <form method="post" action="/bank-accounts/<?= e((string) $account['id']) ?>/delete" onsubmit="return confirm('確定要刪除此銀行帳戶？此操作無法復原（尚有交易紀錄者無法刪除）。');">
                                <?= csrf_field() ?>
                                <button class="btn small danger" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$accounts): ?>
                <tr><td colspan="10" class="empty">尚未建立銀行帳戶。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function bank_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
