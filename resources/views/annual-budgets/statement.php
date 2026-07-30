<?php
$active = 'annual-budgets';
$documentTitle = '經費預算表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card"><span>本年度收益</span><strong><?= e(number_format((float) $statement['income']['current'], 0)) ?></strong></div>
    <div class="stat-card"><span>本年度費損</span><strong><?= e(number_format((float) $statement['expense']['current'], 0)) ?></strong></div>
    <div class="stat-card"><span>本期賸餘 / 短絀</span><strong><?= e(number_format((float) $statement['balance']['current'], 0)) ?></strong></div>
    <div class="stat-card"><span>上年度比較</span><strong><?= e(number_format((float) $statement['balance']['variance'], 0)) ?></strong></div>
</section>

<section class="panel budget-statement">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">主管機關格式</p>
            <h2><?= e($budget['title']) ?></h2>
            <p class="muted-text"><?= e(roc_year_label($budget['fiscal_year'])) ?>，單位：新臺幣元。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/annual-budgets/<?= e((string) $budget['id']) ?>">返回預算</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <div class="statement-title print-only">
        <h2><?= e($profile['foundation_name'] ?? foundation_name()) ?></h2>
        <h3>經費預算表</h3>
        <p>中華民國<?= e((string) roc_year($budget['fiscal_year'])) ?>年度</p>
        <span>單位：新臺幣元</span>
    </div>

    <div class="table-wrap">
        <table class="data-table statement-table">
            <thead>
            <tr>
                <th colspan="5">科目編號</th>
                <th rowspan="3">項目名稱</th>
                <th colspan="2">本年度</th>
                <th colspan="2">上年度</th>
                <th colspan="2">本年度與上年度預算比較</th>
                <th rowspan="3">備註</th>
            </tr>
            <tr>
                <th rowspan="2">款</th>
                <th rowspan="2">項</th>
                <th rowspan="2">目</th>
                <th rowspan="2">次</th>
                <th rowspan="2">節</th>
                <th rowspan="2">預算數</th>
                <th rowspan="2">(A)</th>
                <th rowspan="2">預算數</th>
                <th rowspan="2">(B)</th>
                <th>增(減)數</th>
                <th>增(減)比率%</th>
            </tr>
            <tr>
                <th>(C)=(A)-(B)</th>
                <th>(D)=(C)/(A)*100</th>
            </tr>
            </thead>
            <tbody>
            <?php render_statement_section('income', '收益', $statement); ?>
            <?php render_statement_total('收益合計', $statement['income']); ?>
            <?php render_statement_section('expense', '費損', $statement); ?>
            <?php render_statement_total('費損合計', $statement['expense']); ?>
            <?php render_statement_total('本期稅前賸餘(短絀)', $statement['balance']); ?>
            <tr>
                <td colspan="6">所得稅利益(費用)</td>
                <td class="amount">-</td>
                <td></td>
                <td class="amount">-</td>
                <td></td>
                <td class="amount">-</td>
                <td class="amount">-</td>
                <td></td>
            </tr>
            <?php render_statement_total('本期稅後賸餘(短絀)', $statement['balance']); ?>
            <?php if (!$items): ?>
                <tr><td colspan="13" class="empty-state">尚無預算明細。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($budget['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($budget['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function render_statement_section(string $type, string $label, array $statement): void
{
    echo '<tr class="statement-section"><td>' . ($type === 'income' ? '1' : '2') . '</td><td colspan="4"></td><td>' . e($label) . '</td><td colspan="7"></td></tr>';
    foreach ($statement['groups'][$type] as $group) {
        echo '<tr class="statement-group">';
        echo '<td></td><td></td><td></td><td></td><td></td>';
        echo '<td>' . e($group['name']) . '</td>';
        echo '<td class="amount">' . e(statement_money($group['current'])) . '</td><td></td>';
        echo '<td class="amount">' . e(statement_money($group['previous'])) . '</td><td></td>';
        echo '<td class="amount">' . e(statement_money($group['variance'])) . '</td>';
        echo '<td class="amount">' . e(statement_rate($group['variance_rate'])) . '</td>';
        echo '<td></td></tr>';

        foreach ($group['items'] as $item) {
            $class = !empty($item['is_subtotal']) ? ' class="statement-subtotal"' : '';
            echo '<tr' . $class . '>';
            foreach (['gov_level1', 'gov_level2', 'gov_level3', 'gov_level4', 'gov_level5'] as $key) {
                echo '<td>' . e((string) ($item[$key] ?? '')) . '</td>';
            }
            echo '<td>' . e($item['item_name']) . '</td>';
            echo '<td class="amount">' . e(statement_money($item['amount'])) . '</td><td></td>';
            echo '<td class="amount">' . e(statement_money($item['previous_amount'] ?? 0)) . '</td><td></td>';
            echo '<td class="amount">' . e(statement_money($item['variance_amount'])) . '</td>';
            echo '<td class="amount">' . e(statement_rate($item['variance_rate'])) . '</td>';
            echo '<td>' . e((string) (($item['comparison_note'] ?? '') ?: ($item['notes'] ?? ''))) . '</td>';
            echo '</tr>';
        }
    }
}

function render_statement_total(string $label, array $row): void
{
    echo '<tr class="statement-total">';
    echo '<td colspan="6">' . e($label) . '</td>';
    echo '<td class="amount">' . e(statement_money($row['current'])) . '</td><td></td>';
    echo '<td class="amount">' . e(statement_money($row['previous'])) . '</td><td></td>';
    echo '<td class="amount">' . e(statement_money($row['variance'])) . '</td>';
    echo '<td class="amount">' . e(statement_rate($row['variance_rate'])) . '</td>';
    echo '<td></td></tr>';
}

function statement_money($value): string
{
    $amount = (float) $value;
    return abs($amount) < 0.005 ? '-' : number_format($amount, 0);
}

function statement_rate($value): string
{
    $rate = (float) $value;
    return abs($rate) < 0.005 ? '0.00%' : number_format($rate, 2) . '%';
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
