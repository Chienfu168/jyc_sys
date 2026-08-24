<?php
$active = 'donations';
$documentTitle = '捐款台帳';
$exportQuery = http_build_query([
    'year' => $year,
    'month' => $month,
    'receipt_status' => '',
    'delivery_status' => '',
    'q' => '',
]);
$listQuery = http_build_query(['year' => $year, 'month' => $month]);
$undeliveredQuery = http_build_query([
    'year' => $year,
    'month' => $month,
    'receipt_status' => 'issued',
    'delivery_status' => 'undelivered',
    'q' => '',
]);
$receiptPrintQuery = http_build_query([
    'year' => $year,
    'month' => $month,
    'receipt_status' => 'issued',
    'delivery_status' => '',
    'q' => '',
]);
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card">
        <span>有效筆數</span>
        <strong><?= e(number_format((int) $summary['count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>有效金額</span>
        <strong><?= e(donation_report_money($summary['amount'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>待處理收據</span>
        <strong><?= e(number_format((int) $summary['pending_receipts'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>未寄送收據</span>
        <strong><?= e(number_format((int) $summary['undelivered_receipts'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header no-print">
        <form class="search bank-filter" method="get" action="/donations/report">
            <input type="number" name="year" min="1912" max="2100" value="<?= e((string) $year) ?>" placeholder="西元年度">
            <select name="month">
                <option value="">全年</option>
                <?php foreach (range(1, 12) as $monthNo): ?>
                    <?php $value = str_pad((string) $monthNo, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?= e($value) ?>" <?= $month === $value ? 'selected' : '' ?>><?= e($monthNo . ' 月') ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/donations?<?= e($listQuery) ?>">捐款紀錄</a>
            <a class="btn" href="/donations/income-list?<?= e(http_build_query(['year' => $year])) ?>">捐贈收入清冊</a>
            <?php if ($canExport): ?>
                <a class="btn" href="/donations/export?<?= e($exportQuery) ?>">匯出 CSV</a>
            <?php endif; ?>
            <?php if ((int) $summary['undelivered_receipts'] > 0): ?>
                <a class="btn" href="/donations?<?= e($undeliveredQuery) ?>">未寄送收據</a>
            <?php endif; ?>
            <?php if ((int) $summary['issued_receipts'] > 0): ?>
                <a class="btn" href="/donations/receipts/print?<?= e($receiptPrintQuery) ?>">批次列印收據</a>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('donations.manage') && (int) $summary['pending_receipts'] > 0): ?>
                <form method="post" action="/donations/bulk-issue-receipts" onsubmit="return confirm('確定要批次開立本台帳期間的待處理收據？');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="year" value="<?= e((string) $year) ?>">
                    <input type="hidden" name="month" value="<?= e($month) ?>">
                    <input type="hidden" name="receipt_status" value="pending">
                    <input type="hidden" name="q" value="">
                    <button class="btn" type="submit">批次開立待處理</button>
                </form>
            <?php endif; ?>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>台帳期間</th>
            <td><?= e($month ? roc_date($year . '-' . $month . '-01') . ' 所屬月份' : roc_year_label($year)) ?></td>
            <th>有效捐款合計</th>
            <td class="amount"><?= e(donation_report_money($summary['amount'])) ?></td>
        </tr>
        </tbody>
    </table>

    <h3>未寄送收據</h3>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>收據</th>
                <th>捐款人</th>
                <th>聯絡</th>
                <th class="amount">金額</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($undeliveredReceipts as $row): ?>
                <tr>
                    <td><?= e(roc_date($row['donated_at'])) ?></td>
                    <td class="mono"><?= e($row['receipt_no']) ?></td>
                    <td><a class="text-link" href="/donors/<?= e((string) $row['donor_id']) ?>"><?= e($row['donor_name']) ?></a></td>
                    <td><?= e(donation_report_contact($row)) ?></td>
                    <td class="amount"><?= e(donation_report_money($row['amount'])) ?></td>
                    <td class="actions no-print"><a class="btn small" href="/donations/<?= e((string) $row['id']) ?>">檢視</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$undeliveredReceipts): ?>
                <tr><td colspan="6" class="empty">本期間無未寄送收據。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>月份</th>
                <th class="amount">筆數</th>
                <th class="amount">金額</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($monthlySummary as $row): ?>
                <tr>
                    <td><?= e((string) $row['month_no']) ?> 月</td>
                    <td class="amount"><?= e(number_format((int) $row['donation_count'])) ?></td>
                    <td class="amount"><?= e(donation_report_money($row['total_amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="two-column-report">
        <div>
            <h3>收據狀態</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>狀態</th>
                        <th class="amount">筆數</th>
                        <th class="amount">金額</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($statusSummary as $row): ?>
                        <tr>
                            <td><?= e(donation_report_receipt_label((string) $row['group_name'])) ?></td>
                            <td class="amount"><?= e(number_format((int) $row['donation_count'])) ?></td>
                            <td class="amount"><?= e(donation_report_money($row['total_amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$statusSummary): ?>
                        <tr><td colspan="3" class="empty">本期間尚無資料。</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div>
            <h3>捐款方式</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>方式</th>
                        <th class="amount">筆數</th>
                        <th class="amount">金額</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paymentSummary as $row): ?>
                        <tr>
                            <td><?= e($row['group_name'] ?: '未填寫') ?></td>
                            <td class="amount"><?= e(number_format((int) $row['donation_count'])) ?></td>
                            <td class="amount"><?= e(donation_report_money($row['total_amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$paymentSummary): ?>
                        <tr><td colspan="3" class="empty">本期間尚無資料。</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="two-column-report">
        <div>
            <h3>指定用途</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>用途</th>
                        <th class="amount">筆數</th>
                        <th class="amount">金額</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projectSummary as $row): ?>
                        <tr>
                            <td><?= e($row['group_name']) ?></td>
                            <td class="amount"><?= e(number_format((int) $row['donation_count'])) ?></td>
                            <td class="amount"><?= e(donation_report_money($row['total_amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$projectSummary): ?>
                        <tr><td colspan="3" class="empty">本期間尚無資料。</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div>
            <h3>捐款人排行</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>捐款人</th>
                        <th class="amount">筆數</th>
                        <th class="amount">金額</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($topDonors as $row): ?>
                        <tr>
                            <td>
                                <a class="text-link" href="/donors/<?= e((string) $row['id']) ?>"><?= e($row['name']) ?></a>
                                <div class="muted-text"><?= e(donation_report_donor_type_label((string) $row['donor_type'])) ?> / <?= e(roc_date($row['last_donated_at'])) ?></div>
                            </td>
                            <td class="amount"><?= e(number_format((int) $row['donation_count'])) ?></td>
                            <td class="amount"><?= e(donation_report_money($row['total_amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$topDonors): ?>
                        <tr><td colspan="3" class="empty">本期間尚無資料。</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function donation_report_money($value): string
{
    return number_format((float) $value, 0);
}

function donation_report_receipt_label(string $status): string
{
    return ['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
}

function donation_report_donor_type_label(string $type): string
{
    return ['person' => '個人', 'organization' => '組織'][$type] ?? $type;
}

function donation_report_contact(array $row): string
{
    return $row['donor_email'] ?: ($row['donor_address'] ?: '-');
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
