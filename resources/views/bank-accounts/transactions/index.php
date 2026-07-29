<?php
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>入帳合計</span>
        <strong><?= e(bank_transaction_money($totals['deposit'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>出帳合計</span>
        <strong><?= e(bank_transaction_money($totals['withdrawal'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>撥補零用金</span>
        <strong><?= e(bank_transaction_money($totals['pettyCash'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/bank-transactions">
            <input type="month" name="month" value="<?= e($month) ?>">
            <select name="bank_account_id">
                <option value="0">全部帳戶</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= e((string) $account['id']) ?>" <?= (int) $accountId === (int) $account['id'] ? 'selected' : '' ?>>
                        <?= e($account['bank_name'] . ' ' . $account['account_no']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/bank-accounts">銀行帳戶</a>
            <a class="btn" href="/bank-transactions/reconciliation?month=<?= e($month) ?>&bank_account_id=<?= e((string) $accountId) ?>">銀行對帳</a>
            <?php if (\App\Core\Permission::can('bank_accounts.manage')): ?>
                <a class="btn primary" href="/bank-transactions/create">新增交易</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>帳戶</th>
                <th>類型</th>
                <th>摘要</th>
                <th>對象</th>
                <th>憑證</th>
                <th class="amount">金額</th>
                <th>對帳</th>
                <th>零用金</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?= e($transaction['transacted_on']) ?></td>
                    <td>
                        <strong><?= e($transaction['bank_name']) ?></strong>
                        <div class="muted-text mono"><?= e($transaction['account_no']) ?></div>
                    </td>
                    <td><?= e(bank_transaction_type_label($transaction['transaction_type'])) ?></td>
                    <td><?= e($transaction['subject']) ?></td>
                    <td><?= e($transaction['counterparty'] ?: '-') ?></td>
                    <td><?= e($transaction['reference_no'] ?: '-') ?></td>
                    <td class="amount"><?= e(bank_transaction_money($transaction['amount'])) ?></td>
                    <td><span class="badge <?= ($transaction['reconciliation_status'] ?? 'unreconciled') === 'reconciled' ? 'ok' : 'muted' ?>"><?= e(bank_reconciliation_label($transaction['reconciliation_status'] ?? 'unreconciled')) ?></span></td>
                    <td>
                        <?php if (!empty($transaction['petty_cash_entry_id'])): ?>
                            <span class="badge ok">已同步</span>
                        <?php else: ?>
                            <span class="badge muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$transactions): ?>
                <tr><td colspan="9" class="empty">本月份尚無銀行交易。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function bank_transaction_money($value): string
{
    return number_format((float) $value, 0);
}
function bank_transaction_type_label(string $type): string
{
    return [
        'deposit' => '入帳',
        'withdrawal' => '提款 / 支出',
        'transfer_to_petty_cash' => '撥補零用金',
        'fee' => '銀行手續費',
        'interest' => '利息收入',
    ][$type] ?? $type;
}
function bank_reconciliation_label(string $status): string
{
    return ['unreconciled' => '未對帳', 'reconciled' => '已對帳', 'ignored' => '略過'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
