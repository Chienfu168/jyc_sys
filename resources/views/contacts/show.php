<?php
$active = 'contacts';
$contact = $contact ?? [];
$documentTitle = '聯絡人資料';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($contact['name']) ?>
                <span class="badge <?= $contact['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($contact['status'] === 'active' ? '啟用' : '已封存') ?></span>
            </h2>
            <p class="muted-text"><?= e(trim(($contact['category'] ?? '') . '　' . ($contact['organization'] ?? ''))) ?: '共用通訊錄' ?></p>
        </div>
        <div class="actions no-print">
            <a class="btn" href="/contacts">返回通訊錄</a>
            <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage)): ?>
                <a class="btn" href="/contacts/<?= e((string) $contact['id']) ?>/edit">編輯</a>
                <form method="post" action="/contacts/<?= e((string) $contact['id']) ?>/toggle">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit"><?= $contact['status'] === 'active' ? '封存' : '恢復啟用' ?></button>
                </form>
                <form method="post" action="/contacts/<?= e((string) $contact['id']) ?>/delete" onsubmit="return confirm('確定要刪除此聯絡人？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>姓名</th><td><?= e($contact['name']) ?></td>
            <th>職稱</th><td><?= e($contact['job_title'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>分類</th><td><?= e($contact['category'] ?: '-') ?></td>
            <th>單位／學校</th><td><?= e($contact['organization'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>電話</th><td><?= e($contact['phone'] ?: '-') ?></td>
            <th>手機</th><td><?= e($contact['mobile'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>傳真</th><td><?= e($contact['fax'] ?: '-') ?></td>
            <th>Email</th><td><?= e($contact['email'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>地址</th><td colspan="3"><?= e($contact['address'] ?: '-') ?></td>
        </tr>
        <?php if (!empty($contact['notes'])): ?>
        <tr>
            <th>備註</th><td colspan="3"><?= nl2br(e($contact['notes'])) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="no-print">
            <th>建立人</th><td><?= e($contact['created_by_name'] ?? '-') ?></td>
            <th>更新時間</th><td><?= e($contact['updated_at'] ? substr((string) $contact['updated_at'], 0, 16) : '-') ?></td>
        </tr>
        </tbody>
    </table>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
