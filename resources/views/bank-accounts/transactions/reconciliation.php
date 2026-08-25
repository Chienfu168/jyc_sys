<?php
$documentTitle = '銀行對帳表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>已對帳</span>
        <strong><?= e(number_format((int) $totals['reconciled'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>未對帳</span>
        <strong><?= e(number_format((int) $totals['unreconciled'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>略過</span>
        <strong><?= e(number_format((int) $totals['ignored'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header no-print">
        <form class="search bank-filter" method="get" action="/bank-transactions/reconciliation">
            <?php require base_path('resources/views/shared/date-scope-filter.php'); ?>
            <select name="bank_account_id">
                <option value="0">全部帳戶</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= e((string) $account['id']) ?>" <?= (int) $accountId === (int) $account['id'] ? 'selected' : '' ?>>
                        <?= e($account['bank_name'] . ' ' . $account['account_no']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">全部狀態</option>
                <option value="unreconciled" <?= $status === 'unreconciled' ? 'selected' : '' ?>>未對帳</option>
                <option value="reconciled" <?= $status === 'reconciled' ? 'selected' : '' ?>>已對帳</option>
                <option value="ignored" <?= $status === 'ignored' ? 'selected' : '' ?>>略過</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/bank-transactions?month=<?= e($month) ?>&bank_account_id=<?= e((string) $accountId) ?>">返回銀行交易</a>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>對帳月份</th>
            <td><?= e(roc_date($month . '-01')) ?></td>
            <th>入帳合計</th>
            <td class="amount"><?= e(bank_recon_money($totals['deposit'])) ?></td>
        </tr>
        <tr>
            <th>查詢帳戶</th>
            <td><?= e(bank_recon_account_label($accounts, (int) $accountId)) ?></td>
            <th>出帳合計</th>
            <td class="amount"><?= e(bank_recon_money($totals['withdrawal'])) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>帳戶</th>
                <th>類型</th>
                <th>摘要</th>
                <th>憑證</th>
                <th class="amount">金額</th>
                <th>對帳狀態</th>
                <th>對帳日</th>
                <th class="no-print">更新</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?= e(roc_date($transaction['transacted_on'])) ?></td>
                    <td>
                        <strong><?= e($transaction['bank_name']) ?></strong>
                        <div class="muted-text mono"><?= e($transaction['account_no']) ?></div>
                    </td>
                    <td><?= e(bank_recon_type_label($transaction['transaction_type'])) ?></td>
                    <td>
                        <?= e($transaction['subject'] ?: '-') ?>
                        <?php if (!empty($transaction['reconciliation_note'])): ?>
                            <div class="muted-text"><?= e($transaction['reconciliation_note']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($transaction['reference_no'] ?: '-') ?></td>
                    <td class="amount"><?= e(bank_recon_money($transaction['amount'])) ?></td>
                    <td><?= e(bank_recon_status_label($transaction['reconciliation_status'])) ?></td>
                    <td><?= e(roc_date($transaction['reconciled_on'])) ?></td>
                    <td class="no-print">
                        <?php if (\App\Core\Permission::can('bank_accounts.manage')): ?>
                            <form class="recon-form" method="post" action="/bank-transactions/<?= e((string) $transaction['id']) ?>/reconciliation">
                                <?= csrf_field() ?>
                                <select name="reconciliation_status">
                                    <option value="unreconciled" <?= $transaction['reconciliation_status'] === 'unreconciled' ? 'selected' : '' ?>>未對帳</option>
                                    <option value="reconciled" <?= $transaction['reconciliation_status'] === 'reconciled' ? 'selected' : '' ?>>已對帳</option>
                                    <option value="ignored" <?= $transaction['reconciliation_status'] === 'ignored' ? 'selected' : '' ?>>略過</option>
                                </select>
                                <input type="date" name="reconciled_on" value="<?= e($transaction['reconciled_on'] ?: date('Y-m-d')) ?>">
                                <input type="text" name="reconciliation_note" value="<?= e($transaction['reconciliation_note'] ?? '') ?>" placeholder="對帳備註">
                                <button class="btn small" type="submit">儲存</button>
                            </form>
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

    <?php $signatureContext = 'reconciliation'; ?>
    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function bank_recon_money($value): string
{
    return number_format((float) $value, 0);
}
function bank_recon_status_label(string $status): string
{
    return ['unreconciled' => '未對帳', 'reconciled' => '已對帳', 'ignored' => '略過'][$status] ?? $status;
}
function bank_recon_type_label(string $type): string
{
    return [
        'deposit' => '入帳',
        'withdrawal' => '提款 / 支出',
        'transfer_to_petty_cash' => '撥補零用金',
        'fee' => '銀行手續費',
        'interest' => '利息收入',
    ][$type] ?? $type;
}
function bank_recon_account_label(array $accounts, int $accountId): string
{
    foreach ($accounts as $account) {
        if ((int) $account['id'] === $accountId) {
            return $account['bank_name'] . ' / ' . $account['account_name'] . ' / ' . $account['account_no'];
        }
    }

    return '全部帳戶';
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
