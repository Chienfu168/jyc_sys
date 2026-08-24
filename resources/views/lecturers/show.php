<?php
$active = 'lecturers';
$documentTitle = '講師基本資料';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($lecturer['display_name'] ?: $lecturer['name']) ?></h2>
            <p class="muted-text"><?= e($lecturer['specialty']) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/lecturers">返回列表</a>
            <?php if (\App\Core\Permission::can('lecturers.manage')): ?>
                <a class="btn" href="/lecturers/<?= e((string) $lecturer['id']) ?>/edit">編輯</a>
                <form method="post" action="/lecturers/<?= e((string) $lecturer['id']) ?>/toggle">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit"><?= e($lecturer['status'] === 'active' ? '停用' : '啟用') ?></button>
                </form>
                <form method="post" action="/lecturers/<?= e((string) $lecturer['id']) ?>/delete" onsubmit="return confirm('確定要刪除此講師資料？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>姓名</th>
            <td><?= e($lecturer['name']) ?></td>
            <th>顯示名稱</th>
            <td><?= e($lecturer['display_name'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>身分證字號 / 統編</th>
            <td><?= e($lecturer['tax_id'] ?: '-') ?></td>
            <th>狀態</th>
            <td><?= e($lecturer['status'] === 'active' ? '啟用' : '停用') ?></td>
        </tr>
        <tr>
            <th>服務單位</th>
            <td><?= e($lecturer['organization'] ?: '-') ?></td>
            <th>職稱</th>
            <td><?= e($lecturer['job_title'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>專長</th>
            <td colspan="3"><?= e($lecturer['specialty']) ?></td>
        </tr>
        <tr>
            <th>電話</th>
            <td><?= e($lecturer['phone'] ?: '-') ?></td>
            <th>手機</th>
            <td><?= e($lecturer['mobile'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?= e($lecturer['email'] ?: '-') ?></td>
            <th>常用鐘點費</th>
            <td><?= e(number_format((float) $lecturer['hourly_rate'], 0)) ?></td>
        </tr>
        <tr>
            <th>地址</th>
            <td colspan="3"><?= e($lecturer['address'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>匯款銀行</th>
            <td><?= e($lecturer['bank_name'] ?: '-') ?></td>
            <th>分行</th>
            <td><?= e($lecturer['bank_branch'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>帳號</th>
            <td><?= e($lecturer['bank_account_no'] ?: '-') ?></td>
            <th>戶名</th>
            <td><?= e($lecturer['bank_account_name'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>建檔人</th>
            <td><?= e($lecturer['created_by_name'] ?: '-') ?></td>
            <th>更新時間</th>
            <td><?= e($lecturer['updated_at'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <?php if (!empty($lecturer['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($lecturer['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
