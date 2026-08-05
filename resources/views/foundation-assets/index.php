<?php
$documentTitle = '財產清冊';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card"><span>財產筆數</span><strong><?= e(number_format((int) $summary['active_count'])) ?></strong></div>
    <div class="stat-card"><span>經法院登記</span><strong><?= e(asset_money($summary['court_registered'])) ?></strong></div>
    <div class="stat-card"><span>未經法院登記</span><strong><?= e(asset_money($summary['not_registered'])) ?></strong></div>
    <div class="stat-card"><span>總計</span><strong><?= e(asset_money($summary['total'])) ?></strong></div>
</section>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2>財產清冊</h2>
            <p class="muted-text">依主管機關表格管理財產種類、登記狀態、取得來源、存放地點、證明文件與財產編號。</p>
        </div>
        <div class="actions">
            <form class="search bank-filter" method="get" action="/foundation-assets">
                <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="搜尋名稱、編號、地點">
                <select name="registration_status">
                    <option value="">全部登記狀態</option>
                    <option value="court_registered" <?= $registration === 'court_registered' ? 'selected' : '' ?>>經法院登記</option>
                    <option value="not_registered" <?= $registration === 'not_registered' ? 'selected' : '' ?>>未經法院登記</option>
                </select>
                <select name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>使用中</option>
                    <option value="disposed" <?= $status === 'disposed' ? 'selected' : '' ?>>已除帳</option>
                </select>
                <button class="btn" type="submit">查詢</button>
            </form>
            <?php if (\App\Core\Permission::can('foundation_assets.manage')): ?>
                <a class="btn primary" href="/foundation-assets/create">新增財產</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>登記狀態</th>
                <th>種類</th>
                <th>名稱</th>
                <th>單位</th>
                <th class="amount">數量</th>
                <th class="amount">金額</th>
                <th>取得來源</th>
                <th>存放地點</th>
                <th>證明文件</th>
                <th>財產編號</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($assets as $asset): ?>
                <tr>
                    <td><?= e(asset_registration_label($asset['registration_status'])) ?></td>
                    <td><?= e(asset_kind_label($asset['asset_kind'])) ?></td>
                    <td><strong><?= e($asset['asset_name']) ?></strong></td>
                    <td><?= e($asset['unit'] ?: '-') ?></td>
                    <td class="amount"><?= e(number_format((float) $asset['quantity'], 2)) ?></td>
                    <td class="amount"><?= e(asset_money($asset['amount'])) ?></td>
                    <td><?= e($asset['acquisition_source'] ?: '-') ?></td>
                    <td><?= e($asset['storage_location'] ?: '-') ?></td>
                    <td><?= e($asset['proof_document'] ?: '-') ?></td>
                    <td><?= e($asset['asset_no'] ?: '-') ?></td>
                    <td class="actions no-print">
                        <a class="btn small" href="/foundation-assets/<?= e((string) $asset['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('foundation_assets.manage')): ?>
                            <a class="btn small" href="/foundation-assets/<?= e((string) $asset['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$assets): ?>
                <tr><td colspan="11" class="empty-state">尚無符合條件的財產資料。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="5">總計</th>
                <th class="amount"><?= e(asset_money($summary['total'])) ?></th>
                <th colspan="5"></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function asset_registration_label(string $value): string
{
    return ['court_registered' => '經法院登記', 'not_registered' => '未經法院登記'][$value] ?? $value;
}
function asset_kind_label(string $value): string
{
    return ['movable' => '動產', 'real_estate' => '不動產', 'other' => '其他'][$value] ?? $value;
}
function asset_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
