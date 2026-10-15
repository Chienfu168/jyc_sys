<?php
$active = 'schools';
$schools = $schools ?? [];
$categories = $categories ?? [];
$documentTitle = '學校通訊錄';
ob_start();
?>
<style>
.schools-table td, .schools-table th { white-space: nowrap; }
.schools-table td.wrap, .schools-table th.wrap { white-space: normal; }
@media print {
    .schools-table { font-size: 12px; }
    .schools-table .no-print { display: none !important; }
}
</style>

<div class="print-only" style="text-align:center;margin-bottom:8px">
    <strong style="font-size:16px"><?= e(foundation_name()) ?> — 學校通訊錄</strong>
</div>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>學校通訊錄</h2>
            <p class="muted-text">以學校為中心的聯絡資料，校長、主任等人員任期更替時可保留歷任紀錄，具檢視權限者皆可查閱。</p>
        </div>
        <div class="actions no-print">
            <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage)): ?>
                <a class="btn primary" href="/schools/create">新增學校</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="search bank-filter no-print" method="get" action="/schools">
        <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="學校名稱、地址、電話">
        <select name="category">
            <option value="">全部類別</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>已封存</option>
            <option value="" <?= $status === '' ? 'selected' : '' ?>>全部</option>
        </select>
        <button class="btn" type="submit">查詢</button>
    </form>

    <div class="table-wrap">
        <table class="schools-table">
            <thead>
            <tr>
                <th>類別</th>
                <th class="wrap">學校名稱</th>
                <th>現任校長</th>
                <th class="amount">現任人員</th>
                <th>電話</th>
                <th class="wrap">地址</th>
                <th>狀態</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($schools as $s): ?>
                <tr>
                    <td><?= e($s['category'] ?: '-') ?></td>
                    <td class="wrap"><strong><?= e($s['name']) ?></strong></td>
                    <td><?= e($s['principal_name'] ?: '-') ?></td>
                    <td class="amount"><?= e((string) $s['current_staff_count']) ?></td>
                    <td><?= e($s['phone'] ?: '-') ?></td>
                    <td class="wrap"><?= e($s['address'] ?: '-') ?></td>
                    <td><span class="badge <?= $s['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($s['status'] === 'active' ? '啟用' : '已封存') ?></span></td>
                    <td class="actions no-print">
                        <a class="btn small" href="/schools/<?= e((string) $s['id']) ?>">檢視</a>
                        <?php if (!empty($canManage)): ?>
                            <a class="btn small" href="/schools/<?= e((string) $s['id']) ?>/edit">編輯</a>
                            <form method="post" action="/schools/<?= e((string) $s['id']) ?>/delete" onsubmit="return confirm('確定要刪除此學校？將一併刪除其人員紀錄，此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$schools): ?>
                <tr><td colspan="8" class="empty">尚無學校資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="muted-text no-print" style="margin-top:8px">共 <?= e((string) count($schools)) ?> 筆。</p>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
