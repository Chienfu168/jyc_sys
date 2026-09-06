<?php
$active = 'bank-slips';
$slips = $slips ?? [];
$banks = $banks ?? [];
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>匯款單(取款憑條)</h2>
            <p class="muted-text">製作可交付銀行的匯款單並直接列印。目前支援彰化銀行,匯款人與取款帳戶資料由「匯款單設定」帶入。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/finance">返回財務會計</a>
            <?php if (!empty($canManage)): ?>
                <a class="btn" href="/bank-slips/settings">匯款單設定</a>
                <?php foreach (\App\Support\BankSlipCatalog::enabled() as $code => $bank): ?>
                    <a class="btn primary" href="/bank-slips/create?bank=<?= e($code) ?>">製作<?= e($bank['name']) ?>匯款單</a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>銀行</th>
                <th>收款人</th>
                <th>收款帳號</th>
                <th class="amount">金額</th>
                <th>取款帳戶</th>
                <th>製表人</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($slips as $slip): ?>
                <tr>
                    <td class="mono"><?= e(roc_date($slip['slip_date'])) ?></td>
                    <td><?= e(\App\Support\BankSlipCatalog::name((string) $slip['bank_code'])) ?></td>
                    <td><strong><?= e($slip['payee_name']) ?></strong></td>
                    <td class="mono"><?= e($slip['payee_account']) ?></td>
                    <td class="amount"><?= e(number_format((float) $slip['amount'], 0)) ?></td>
                    <td class="muted-text"><?= e($slip['withdrawal_bank_name'] ? $slip['withdrawal_bank_name'] . ' ' . $slip['withdrawal_account_no'] : '-') ?></td>
                    <td class="muted-text"><?= e($slip['created_by_name'] ?: '-') ?></td>
                    <td class="actions">
                        <a class="btn small" href="/bank-slips/<?= e((string) $slip['id']) ?>">列印</a>
                        <?php if (!empty($canManage) || owns_record($slip['created_by'] ?? null)): ?>
                            <form method="post" action="/bank-slips/<?= e((string) $slip['id']) ?>/delete" onsubmit="return confirm('確定要刪除此匯款單？');">
                                <?= csrf_field() ?>
                                <button class="btn small danger" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$slips): ?>
                <tr><td colspan="8" class="empty">尚無匯款單。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
