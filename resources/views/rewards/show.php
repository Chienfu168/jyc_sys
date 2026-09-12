<?php
use App\Domain\Bank\TwCurrency;
$active = 'rewards';
$reward = $reward ?? [];
$recipients = $recipients ?? [];
$status = (string) $reward['status'];
$statusColors = ['draft' => '#6b7280', 'submitted' => '#9a6a00', 'approved' => '#1b7a43', 'rejected' => '#b32d2d'];
$statusLabels = ['draft' => '草稿', 'submitted' => '待核定', 'approved' => '已核定', 'rejected' => '已退回'];
$editable = in_array($status, ['draft', 'rejected'], true);
$documentTitle = '員工專案及創新成果獎勵申請書';
$money = static fn ($v): string => number_format((float) $v, 0);
$total = (float) $reward['total_amount'];
$totalUpper = TwCurrency::uppercase((int) round($total));

$roc = '';
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $reward['apply_date'], $m)) {
    $roc = '中華民國 ' . ((int) $m[1] - 1911) . ' 年 ' . (int) $m[2] . ' 月 ' . (int) $m[3] . ' 日';
}

$signatureRoles = [
    ['label' => '申請人', 'name' => $reward['applicant_name'] ?? ''],
    ['label' => '會計', 'name' => ''],
    ['label' => '執行長', 'name' => ''],
    ['label' => '董事長', 'name' => ''],
];
ob_start();
?>
<style>
.rw-doc h3 { margin: 18px 0 6px; font-size: 15px; }
.rw-head { width: 100%; border-collapse: collapse; margin-top: 6px; }
.rw-head th, .rw-head td { border: 1px solid #cfd6d2; padding: 6px 10px; }
.rw-head th { background: #f1f4f3; text-align: left; white-space: nowrap; width: 110px; }
.rw-para { line-height: 1.9; text-align: justify; margin: 4px 0; }
.rw-recip { width: 100%; border-collapse: collapse; margin-top: 6px; }
.rw-recip th, .rw-recip td { border: 1px solid #cfd6d2; padding: 6px 10px; vertical-align: top; }
.rw-recip thead th { background: #f1f4f3; text-align: left; }
.rw-recip td.amount, .rw-recip th.amount { text-align: right; width: 150px; white-space: nowrap; }
.rw-recip tfoot td { background: #f6f8f7; font-weight: 700; }
.rw-date { text-align: center; margin-top: 24px; letter-spacing: 2px; }
</style>

<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>員工獎勵申請 <?= e($reward['application_no']) ?>
                <span style="color:<?= e($statusColors[$status] ?? '#333') ?>;font-weight:600;font-size:14px">（<?= e($statusLabels[$status] ?? $status) ?>）</span>
            </h2>
            <p class="muted-text"><?= e($reward['title']) ?></p>
        </div>
        <div class="actions no-print">
            <a class="btn" href="/rewards">返回清單</a>
            <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage) && $editable): ?>
                <a class="btn" href="/rewards/<?= e((string) $reward['id']) ?>/edit">編輯</a>
            <?php endif; ?>
            <?php if ($status === 'draft'): ?>
                <form method="post" action="/rewards/<?= e((string) $reward['id']) ?>/submit">
                    <?= csrf_field() ?>
                    <button class="btn primary" type="submit">送出申請</button>
                </form>
            <?php endif; ?>
            <?php if (!empty($canManage) && $editable): ?>
                <form method="post" action="/rewards/<?= e((string) $reward['id']) ?>/delete" onsubmit="return confirm('確定要刪除此獎勵申請？');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="print-only" style="text-align:center;margin-bottom:10px">
        <strong style="font-size:18px">員工專案及創新成果獎勵申請書</strong>
    </div>

    <div class="rw-doc">
        <table class="rw-head">
            <tbody>
            <tr><th>申請項目</th><td><?= e($reward['title']) ?></td></tr>
            <?php if (!empty($reward['award_reason'])): ?>
            <tr><th>獎勵事由</th><td><?= nl2br(e($reward['award_reason'])) ?></td></tr>
            <?php endif; ?>
            <tr><th>獎勵總額</th><td>新臺幣<?= e($totalUpper) ?>（NT$<?= e($money($total)) ?>）</td></tr>
        </tbody>
        </table>

        <?php if (!empty($reward['reason'])): ?>
        <h3>一、申請事由</h3>
        <div class="rw-para"><?= nl2br(e($reward['reason'])) ?></div>
        <?php endif; ?>

        <h3>二、獎勵人員及金額</h3>
        <table class="rw-recip">
            <thead>
                <tr>
                    <th style="width:16%">人員</th>
                    <th style="width:16%">職務</th>
                    <th>參與性質</th>
                    <th class="amount">建議獎勵金額</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recipients as $rp): ?>
                <tr>
                    <td><?= e($rp['name']) ?></td>
                    <td><?= e($rp['job_title'] ?: '-') ?></td>
                    <td><?= nl2br(e($rp['contribution'] ?? '')) ?></td>
                    <td class="amount">NT$<?= e($money($rp['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recipients): ?>
                <tr><td colspan="4" class="empty">尚無獎勵人員。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right">合計</td>
                    <td class="amount">NT$<?= e($money($total)) ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($reward['justification'])): ?>
        <h3>三、獎勵理由</h3>
        <div class="rw-para"><?= nl2br(e($reward['justification'])) ?></div>
        <?php endif; ?>

        <?php if (!empty($reward['funding_source'])): ?>
        <h3>四、經費</h3>
        <div class="rw-para"><?= nl2br(e($reward['funding_source'])) ?></div>
        <?php endif; ?>

        <?php if (!empty($reward['proposed_action'])): ?>
        <h3>五、擬辦</h3>
        <div class="rw-para"><?= nl2br(e($reward['proposed_action'])) ?></div>
        <?php endif; ?>

        <?php if (!empty($reward['review_notes'])): ?>
        <div class="rw-para no-print" style="color:#1d5fa8">核定意見：<?= nl2br(e($reward['review_notes'])) ?></div>
        <?php endif; ?>

        <?php require base_path('resources/views/shared/signatures.php'); ?>

        <div class="rw-date"><?= e($roc) ?></div>
    </div>
</section>

<?php if ($status === 'submitted' && !empty($canApprove)): ?>
<section class="panel no-print">
    <div class="panel-header"><div><h2>核定</h2></div></div>
    <form method="post" action="/rewards/<?= e((string) $reward['id']) ?>/approve">
        <?= csrf_field() ?>
        <label><span>核定意見（選填）</span><textarea name="review_notes" rows="2"></textarea></label>
        <div class="form-actions">
            <button class="btn primary" type="submit">核定</button>
        </div>
    </form>
    <form method="post" action="/rewards/<?= e((string) $reward['id']) ?>/reject" onsubmit="return confirm('確定要退回此申請？');" style="margin-top:8px">
        <?= csrf_field() ?>
        <input type="hidden" name="review_notes" value="">
        <button class="btn" type="submit">退回</button>
    </form>
</section>
<?php endif; ?>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
