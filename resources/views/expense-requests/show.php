<?php
use App\Domain\ExpenseRequests\ExpenseRequestSupport;
$active = 'expense-requests';
$request = $request ?? [];
$attachments = $attachments ?? [];
$status = (string) $request['status'];
$statusColors = ['draft' => '#6b7280', 'submitted' => '#9a6a00', 'approved' => '#1d5fa8', 'rejected' => '#b32d2d', 'paid' => '#1b7a43'];
$editable = in_array($status, ['draft', 'rejected'], true) && (!empty($isOwner) || !empty($canApprove));
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>費用申請 <?= e($request['request_no']) ?></h2>
            <p class="muted-text"><?= e(roc_date($request['occurred_on'])) ?> ／
                <span style="color:<?= e($statusColors[$status] ?? '#333') ?>;font-weight:600"><?= e(ExpenseRequestSupport::statusLabel($status)) ?></span>
            </p>
        </div>
        <div class="actions">
            <a class="btn" href="/expense-requests">返回清單</a>
            <?php if ($editable): ?>
                <a class="btn" href="/expense-requests/<?= e((string) $request['id']) ?>/edit">編輯</a>
            <?php endif; ?>
            <?php if ($status === 'draft' && !empty($isOwner)): ?>
                <form method="post" action="/expense-requests/<?= e((string) $request['id']) ?>/submit">
                    <?= csrf_field() ?>
                    <button class="btn primary" type="submit">送出申請</button>
                </form>
            <?php endif; ?>
            <?php if (in_array($status, ['draft', 'rejected'], true) && (!empty($isOwner) || !empty($canApprove))): ?>
                <form method="post" action="/expense-requests/<?= e((string) $request['id']) ?>/delete" onsubmit="return confirm('確定要刪除此費用申請？');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>申請人</th><td><?= e($request['applicant_name'] ?? '-') ?></td>
            <th>金額</th><td><?= e(number_format((float) $request['amount'])) ?> 元</td>
        </tr>
        <tr>
            <th>費用項目</th><td><?= e($request['item_name']) ?></td>
            <th>收款方式</th><td><?= e(ExpenseRequestSupport::paymentLabel($request['payment_type'])) ?></td>
        </tr>
        <?php if ($request['payment_type'] === 'bank'): ?>
        <tr>
            <th>匯款資料</th>
            <td colspan="3"><?= e(trim(($request['bank_name'] ?? '') . ' ' . ($request['bank_branch'] ?? ''))) ?>　帳號：<?= e($request['bank_account'] ?: '-') ?>　戶名：<?= e($request['bank_account_name'] ?: '-') ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($request['reason'])): ?>
        <tr><th>事由／說明</th><td colspan="3"><?= nl2br(e($request['reason'])) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($request['reviewed_by_name'])): ?>
        <tr>
            <th>核定人</th><td><?= e($request['reviewed_by_name']) ?></td>
            <th>核定時間</th><td><?= e($request['reviewed_at'] ? roc_date(substr($request['reviewed_at'], 0, 10)) : '-') ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($request['review_notes'])): ?>
        <tr><th>核定意見</th><td colspan="3"><?= nl2br(e($request['review_notes'])) ?></td></tr>
        <?php endif; ?>
        <?php if ($status === 'paid'): ?>
        <tr>
            <th>付款人</th><td><?= e($request['paid_by_name'] ?? '-') ?></td>
            <th>付款方式／時間</th><td><?= e(ExpenseRequestSupport::paymentLabel($request['paid_method'])) ?>　<?= e($request['paid_at'] ? roc_date(substr($request['paid_at'], 0, 10)) : '') ?></td>
        </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($status === 'submitted' && !empty($canApprove)): ?>
        <div class="form-section">
            <h3>核定</h3>
            <form method="post" action="/expense-requests/<?= e((string) $request['id']) ?>/approve">
                <?= csrf_field() ?>
                <label><span>核定意見（選填）</span><textarea name="review_notes" rows="2"></textarea></label>
                <div class="form-actions">
                    <button class="btn primary" type="submit">核定（併入零用金）</button>
                </div>
            </form>
            <form method="post" action="/expense-requests/<?= e((string) $request['id']) ?>/reject" onsubmit="return confirm('確定要退回此申請？');" style="margin-top:8px">
                <?= csrf_field() ?>
                <input type="hidden" name="review_notes" value="">
                <button class="btn" type="submit">退回</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($status === 'approved' && !empty($canPay)): ?>
        <div class="form-section">
            <h3>確認付款</h3>
            <p class="muted-text">已核定並併入零用金<?= !empty($request['petty_cash_entry_id']) ? '（零用金 #' . e((string) $request['petty_cash_entry_id']) . '）' : '' ?>。確認支付給申請者後標記為已付款。</p>
            <form method="post" action="/expense-requests/<?= e((string) $request['id']) ?>/pay">
                <?= csrf_field() ?>
                <div class="grid-form">
                    <label>
                        <span>付款方式</span>
                        <select name="paid_method">
                            <option value="cash" <?= $request['payment_type'] !== 'bank' ? 'selected' : '' ?>>現金</option>
                            <option value="bank" <?= $request['payment_type'] === 'bank' ? 'selected' : '' ?>>匯款</option>
                        </select>
                    </label>
                    <label>
                        <span>付款備註（選填）</span>
                        <input type="text" name="payment_notes" maxlength="255">
                    </label>
                </div>
                <div class="form-actions">
                    <button class="btn primary" type="submit">確認付款</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <div><h2>憑證附件</h2><p class="muted-text">代墊費用的憑證照片（已壓縮）。</p></div>
    </div>
    <?php if ($attachments): ?>
        <div class="pcq-attach-grid">
            <?php foreach ($attachments as $file): ?>
                <?php $isImage = str_starts_with((string) ($file['mime_type'] ?? ''), 'image/'); ?>
                <figure class="pcq-attach">
                    <a href="/expense-requests/<?= e((string) $request['id']) ?>/attachments/<?= e((string) $file['id']) ?>" target="_blank" rel="noopener">
                        <?php if ($isImage): ?>
                            <img src="/expense-requests/<?= e((string) $request['id']) ?>/attachments/<?= e((string) $file['id']) ?>" alt="憑證" loading="lazy">
                        <?php else: ?>
                            <span class="pcq-attach__file">PDF</span>
                        <?php endif; ?>
                    </a>
                    <figcaption>
                        <span class="muted-text"><?= e(number_format(((int) ($file['file_size'] ?? 0)) / 1024, 0)) ?> KB</span>
                        <?php if (!empty($isOwner) || !empty($canApprove)): ?>
                            <form method="post" action="/expense-requests/<?= e((string) $request['id']) ?>/attachments/<?= e((string) $file['id']) ?>/delete" onsubmit="return confirm('確定要刪除此憑證？');">
                                <?= csrf_field() ?>
                                <button class="btn" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="muted-text">尚無憑證附件。</p>
    <?php endif; ?>
    <?php if (!empty($isOwner) || !empty($canApprove)): ?>
        <form method="post" action="/expense-requests/<?= e((string) $request['id']) ?>/attachments" enctype="multipart/form-data" class="form" style="margin-top:14px">
            <?= csrf_field() ?>
            <label class="pcq-field">
                <span class="pcq-label">新增憑證（照片會自動壓縮）</span>
                <input type="file" name="receipts[]" accept="image/*,application/pdf" multiple required>
            </label>
            <div class="form-actions">
                <button class="btn primary" type="submit">上傳憑證</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php if (!empty($approvalHistory)): ?>
<section class="panel">
    <div class="panel-header"><div><h2>簽核紀錄</h2></div></div>
    <table class="data-table">
        <thead><tr><th>動作</th><th>狀態</th><th>意見</th><th>時間</th></tr></thead>
        <tbody>
        <?php foreach ($approvalHistory as $h): ?>
            <tr>
                <td><?= e(['submit' => '送審', 'approved' => '核定', 'rejected' => '退回'][$h['action']] ?? $h['action']) ?></td>
                <td><?= e(['pending' => '待審', 'approved' => '已核准', 'rejected' => '已退回'][$h['status']] ?? $h['status']) ?></td>
                <td><?= e($h['request_notes'] ?? $h['review_notes'] ?? '') ?></td>
                <td><?= e($h['updated_at'] ? roc_date(substr($h['updated_at'], 0, 10)) : '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
