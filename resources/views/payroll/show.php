<?php
$active = 'payroll';
$documentTitle = '薪資明細表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($record['employee_name']) ?> / <?= e($record['payroll_month']) ?></h2>
            <p class="muted-text"><?= e(($record['department'] ?: '-') . ' / ' . ($record['job_title'] ?: '-')) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/payroll">返回列表</a>
            <?php if (\App\Core\Permission::can('payroll.manage')): ?>
                <a class="btn" href="/payroll/<?= e((string) $record['id']) ?>/edit">編輯</a>
                <?php if ($record['payment_status'] === 'draft'): ?>
                    <form method="post" action="/payroll/<?= e((string) $record['id']) ?>/confirm">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">確認</button>
                    </form>
                <?php endif; ?>
                <?php if ($record['payment_status'] !== 'paid' && $record['payment_status'] !== 'voided'): ?>
                    <form method="post" action="/payroll/<?= e((string) $record['id']) ?>/mark-paid">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">標記已付款</button>
                    </form>
                <?php endif; ?>
                <?php if (\App\Core\Permission::can('accounting.manage') && in_array($record['payment_status'], ['confirmed', 'paid'], true) && empty($record['accounting_voucher_id'])): ?>
                    <form method="post" action="/payroll/<?= e((string) $record['id']) ?>/voucher">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">建立會計傳票</button>
                    </form>
                <?php endif; ?>
                <?php if (!empty($record['accounting_voucher_id'])): ?>
                    <a class="btn" href="/accounting/vouchers/<?= e((string) $record['accounting_voucher_id']) ?>">查看傳票</a>
                <?php endif; ?>
                <?php if ($record['payment_status'] !== 'voided'): ?>
                    <form method="post" action="/payroll/<?= e((string) $record['id']) ?>/void">
                        <?= csrf_field() ?>
                        <button class="btn" type="submit">作廢</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('payroll.delete') || owns_record($record['created_by'] ?? null)): ?>
                <form method="post" action="/payroll/<?= e((string) $record['id']) ?>/delete" onsubmit="return confirm('確定要刪除此薪資紀錄？此操作無法復原（已建立會計傳票者無法刪除）。');">
                    <?= csrf_field() ?>
                    <button class="btn danger" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>薪資月份</th>
            <td><?= e($record['payroll_month']) ?></td>
            <th>發薪日期</th>
            <td><?= e(roc_date($record['pay_date'])) ?></td>
        </tr>
        <tr>
            <th>員工</th>
            <td><?= e($record['employee_name']) ?></td>
            <th>員工編號</th>
            <td><?= e($record['employee_no'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>部門</th>
            <td><?= e($record['department'] ?: '-') ?></td>
            <th>職稱</th>
            <td><?= e($record['job_title'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>付款方式</th>
            <td><?= e($record['payment_method'] ?: '-') ?></td>
            <th>狀態</th>
            <td><?= e(payroll_show_status_label($record['payment_status'])) ?></td>
        </tr>
        <tr>
            <th>會計傳票</th>
            <td colspan="3">
                <?php if (!empty($record['accounting_voucher_id'])): ?>
                    <a class="link" href="/accounting/vouchers/<?= e((string) $record['accounting_voucher_id']) ?>"><?= e($record['voucher_no'] ?: '查看傳票') ?></a>
                    <span class="muted-text">（<?= e(payroll_show_voucher_status_label((string) ($record['voucher_status'] ?? ''))) ?>）</span>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>應發項目</th>
                <th class="amount">金額</th>
                <th>扣款項目</th>
                <th class="amount">金額</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>本薪</td>
                <td class="amount"><?= e(number_format((float) $record['base_salary'], 0)) ?></td>
                <td>勞保扣款</td>
                <td class="amount"><?= e(number_format((float) $record['labor_insurance_deduction'], 0)) ?></td>
            </tr>
            <tr>
                <td>津貼</td>
                <td class="amount"><?= e(number_format((float) $record['allowance_total'], 0)) ?></td>
                <td>健保扣款</td>
                <td class="amount"><?= e(number_format((float) $record['health_insurance_deduction'], 0)) ?></td>
            </tr>
            <tr>
                <td>加班費</td>
                <td class="amount"><?= e(number_format((float) $record['overtime_pay'], 0)) ?></td>
                <td>自提退休金</td>
                <td class="amount"><?= e(number_format((float) $record['pension_self_deduction'], 0)) ?></td>
            </tr>
            <tr>
                <td>獎金</td>
                <td class="amount"><?= e(number_format((float) $record['bonus'], 0)) ?></td>
                <td>所得稅</td>
                <td class="amount"><?= e(number_format((float) $record['income_tax'], 0)) ?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td>請假扣款 / 其他扣款</td>
                <td class="amount"><?= e(number_format((float) $record['leave_deduction'] + (float) $record['other_deduction'], 0)) ?></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th>應發合計</th>
                <th class="amount"><?= e(number_format((float) $record['gross_pay'], 0)) ?></th>
                <th>扣款合計</th>
                <th class="amount"><?= e(number_format((float) $record['deduction_total'], 0)) ?></th>
            </tr>
            <tr>
                <th colspan="3">實發金額</th>
                <th class="amount"><?= e(number_format((float) $record['net_pay'], 0)) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>雇主退休金提繳</th>
            <td><?= e(number_format((float) $record['employer_pension'], 0)) ?></td>
            <th>付款日期</th>
            <td><?= e(roc_date($record['paid_on'])) ?></td>
        </tr>
        <tr>
            <th>基金會付款銀行</th>
            <td><?= e($record['bank_name'] ? $record['bank_name'] . ' ' . $record['account_no'] : '-') ?></td>
            <th>薪轉帳戶</th>
            <td><?= e(trim(($record['employee_bank_name'] ?: '-') . ' ' . ($record['employee_bank_branch'] ?: '') . ' ' . ($record['employee_bank_account_no'] ?: ''))) ?></td>
        </tr>
        <tr>
            <th>建檔人</th>
            <td><?= e($record['created_by_name'] ?: '-') ?></td>
            <th>更新時間</th>
            <td><?= e($record['updated_at'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <?php if (!empty($record['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($record['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function payroll_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'confirmed' => '已確認', 'paid' => '已付款', 'voided' => '作廢'][$status] ?? $status;
}
function payroll_show_voucher_status_label(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已過帳', 'voided' => '作廢'][$status] ?? ($status ?: '-');
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
