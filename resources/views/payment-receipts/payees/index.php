<?php
$active = 'payment-receipts';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <h2>常用領款人</h2>
        <div class="actions">
            <a class="btn" href="/payment-receipts">返回領款收據</a>
            <a class="btn primary" href="/payment-receipt-payees/create">新增常用領款人</a>
        </div>
    </div>
    <p class="muted-text">建立經常領款者的資料，開立領據時可一鍵帶入姓名、身分證字號與匯款資訊。刪除或停用不影響已開立的領據。</p>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>領款者</th>
                <th>身分證字號</th>
                <th>付款方式</th>
                <th>銀行 / 帳號</th>
                <th>排序</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($payees as $payee): ?>
                <tr>
                    <td><strong><?= e($payee['payee_name']) ?></strong></td>
                    <td><?= e($payee['payee_tax_id'] ?: '-') ?></td>
                    <td><?= e($payee['payment_type'] === 'cash' ? '現金' : '匯款') ?></td>
                    <td>
                        <?php if ($payee['payment_type'] === 'bank'): ?>
                            <?= e(trim(($payee['bank_name'] ?? '') . ' ' . ($payee['bank_branch'] ?? ''))) ?: '-' ?>
                            <?php if (!empty($payee['bank_account'])): ?>
                                <br><span class="mono muted-text"><?= e($payee['bank_account']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= e((string) $payee['sort_order']) ?></td>
                    <td><span class="badge <?= $payee['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($payee['status'] === 'active' ? '啟用' : '停用') ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/payment-receipt-payees/<?= e((string) $payee['id']) ?>/edit">編輯</a>
                        <form method="post" action="/payment-receipt-payees/<?= e((string) $payee['id']) ?>/toggle">
                            <?= csrf_field() ?>
                            <button class="btn small ghost" type="submit"><?= $payee['status'] === 'active' ? '停用' : '啟用' ?></button>
                        </form>
                        <form method="post" action="/payment-receipt-payees/<?= e((string) $payee['id']) ?>/delete" onsubmit="return confirm('確定要「刪除」此常用領款人？不影響已開立的領據。');">
                            <?= csrf_field() ?>
                            <button class="btn small danger" type="submit">刪除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$payees): ?>
                <tr><td colspan="7" class="empty">目前沒有常用領款人</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
