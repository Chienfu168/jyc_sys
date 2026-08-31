<?php
use App\Domain\ExpenseRequests\ExpenseRequestSupport;
$active = 'expense-requests';
$requests = $requests ?? [];
$statusColors = ['draft' => '#6b7280', 'submitted' => '#9a6a00', 'approved' => '#1d5fa8', 'rejected' => '#b32d2d', 'paid' => '#1b7a43'];
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>費用申請</h2>
            <p class="muted-text"><?= !empty($canReview) ? '所有員工的費用申請;可核定與付款。' : '我的費用申請;核定後併入零用金,由會計付款。' ?></p>
        </div>
        <div class="actions">
            <a class="btn primary" href="/expense-requests/create">新增申請</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>單號</th>
                <th>日期</th>
                <?php if (!empty($canReview)): ?><th>申請人</th><?php endif; ?>
                <th>項目</th>
                <th style="text-align:right">金額</th>
                <th>收款</th>
                <th>狀態</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= e($r['request_no']) ?></td>
                    <td><?= e(roc_date($r['occurred_on'])) ?></td>
                    <?php if (!empty($canReview)): ?><td><?= e($r['applicant_name'] ?? '-') ?></td><?php endif; ?>
                    <td><?= e($r['item_name']) ?></td>
                    <td style="text-align:right"><?= e(number_format((float) $r['amount'])) ?></td>
                    <td><?= e(ExpenseRequestSupport::paymentLabel($r['payment_type'])) ?></td>
                    <td><span style="color:<?= e($statusColors[$r['status']] ?? '#333') ?>;font-weight:600"><?= e(ExpenseRequestSupport::statusLabel($r['status'])) ?></span></td>
                    <td><a class="btn" href="/expense-requests/<?= e((string) $r['id']) ?>">檢視</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?>
                <tr><td colspan="<?= !empty($canReview) ? 8 : 7 ?>" class="empty">尚無費用申請。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
