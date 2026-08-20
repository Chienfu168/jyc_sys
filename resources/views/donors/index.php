<?php
$active = 'donors';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>捐款人數</span>
        <strong><?= e(number_format((int) $summary['total'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>捐款筆數</span>
        <strong><?= e(number_format((int) $summary['donations'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>累計捐款</span>
        <strong><?= e(donor_index_money($summary['amount'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/donors">
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="姓名、電話、Email、收據抬頭">
            <select name="type">
                <option value="">全部類型</option>
                <option value="person" <?= $type === 'person' ? 'selected' : '' ?>>個人</option>
                <option value="organization" <?= $type === 'organization' ? 'selected' : '' ?>>組織</option>
            </select>
            <select name="status">
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>封存</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <?php if (\App\Core\Permission::can('donors.manage')): ?>
            <a class="btn primary" href="/donors/create">新增捐款人</a>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>捐款人</th>
                <th>類型</th>
                <th>聯絡方式</th>
                <th>收據抬頭</th>
                <th>最近捐款</th>
                <th class="amount">累計金額</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($donors as $donor): ?>
                <tr>
                    <td><a class="text-link" href="/donors/<?= e((string) $donor['id']) ?>"><?= e($donor['name']) ?></a></td>
                    <td><?= e(donor_index_type_label($donor['donor_type'])) ?></td>
                    <td>
                        <?= e($donor['phone'] ?: '-') ?>
                        <?php if (!empty($donor['email'])): ?>
                            <div class="muted-text"><?= e($donor['email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= e($donor['receipt_title'] ?: '-') ?>
                        <?php if (!empty($donor['tax_id'])): ?>
                            <div class="muted-text mono"><?= e($donor['tax_id']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e(!empty($donor['last_donated_at']) ? roc_date($donor['last_donated_at']) : '-') ?></td>
                    <td class="amount"><?= e(donor_index_money($donor['donation_total'])) ?></td>
                    <td><span class="badge <?= $donor['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($donor['status'] === 'active' ? '啟用' : '封存') ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/donors/<?= e((string) $donor['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('donors.manage')): ?>
                            <a class="btn small" href="/donors/<?= e((string) $donor['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$donors): ?>
                <tr><td colspan="8" class="empty">沒有符合條件的捐款人。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function donor_index_money($value): string
{
    return number_format((float) $value, 0);
}

function donor_index_type_label(string $type): string
{
    return ['person' => '個人', 'organization' => '組織'][$type] ?? $type;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
