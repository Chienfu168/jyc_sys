<?php
$active = 'contacts';
$contacts = $contacts ?? [];
$categories = $categories ?? [];
$documentTitle = '通訊錄';
ob_start();
?>
<style>
.contacts-table td, .contacts-table th { white-space: nowrap; }
.contacts-table td.wrap, .contacts-table th.wrap { white-space: normal; }
@media print {
    .contacts-table { font-size: 12px; }
    .contacts-table .no-print { display: none !important; }
}
</style>

<div class="print-only" style="text-align:center;margin-bottom:8px">
    <strong style="font-size:16px"><?= e(foundation_name()) ?> — 通訊錄</strong>
</div>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>通訊錄</h2>
            <p class="muted-text">全機構共用的外部聯絡人（各學校、政府機關、合作單位、廠商等），具檢視權限者皆可查閱。</p>
        </div>
        <div class="actions no-print">
            <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage)): ?>
                <a class="btn primary" href="/contacts/create">新增聯絡人</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="search bank-filter no-print" method="get" action="/contacts">
        <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="姓名、單位、職稱、電話、Email">
        <select name="category">
            <option value="">全部分類</option>
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
        <table class="contacts-table">
            <thead>
            <tr>
                <th>分類</th>
                <th class="wrap">單位</th>
                <th>姓名</th>
                <th>職稱</th>
                <th>電話</th>
                <th>手機</th>
                <th>Email</th>
                <th>狀態</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($contacts as $c): ?>
                <tr>
                    <td><?= e($c['category'] ?: '-') ?></td>
                    <td class="wrap"><?= e($c['organization'] ?: '-') ?></td>
                    <td><strong><?= e($c['name']) ?></strong></td>
                    <td><?= e($c['job_title'] ?: '-') ?></td>
                    <td><?= e($c['phone'] ?: '-') ?></td>
                    <td><?= e($c['mobile'] ?: '-') ?></td>
                    <td><?= e($c['email'] ?: '-') ?></td>
                    <td><span class="badge <?= $c['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($c['status'] === 'active' ? '啟用' : '已封存') ?></span></td>
                    <td class="actions no-print">
                        <a class="btn small" href="/contacts/<?= e((string) $c['id']) ?>">檢視</a>
                        <?php if (!empty($canManage)): ?>
                            <a class="btn small" href="/contacts/<?= e((string) $c['id']) ?>/edit">編輯</a>
                            <form method="post" action="/contacts/<?= e((string) $c['id']) ?>/delete" onsubmit="return confirm('確定要刪除此聯絡人？此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$contacts): ?>
                <tr><td colspan="9" class="empty">尚無聯絡人資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="muted-text no-print" style="margin-top:8px">共 <?= e((string) count($contacts)) ?> 筆。</p>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
