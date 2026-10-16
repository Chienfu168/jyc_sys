<?php
$active = 'board-directors';
$director = $director ?? [];
$documentTitle = '董事資料';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($director['name']) ?>
                <span class="badge <?= $director['status'] === 'current' ? 'ok' : 'muted' ?>"><?= e($director['status'] === 'current' ? '現任' : '前任') ?></span>
            </h2>
            <p class="muted-text"><?= e($director['title'] ?: '董事名冊') ?></p>
        </div>
        <div class="actions no-print">
            <a class="btn" href="/board-directors">返回董事名冊</a>
            <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage)): ?>
                <a class="btn" href="/board-directors/<?= e((string) $director['id']) ?>/edit">編輯</a>
                <form method="post" action="/board-directors/<?= e((string) $director['id']) ?>/toggle">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit"><?= $director['status'] === 'current' ? '標示卸任' : '恢復現任' ?></button>
                </form>
                <form method="post" action="/board-directors/<?= e((string) $director['id']) ?>/delete" onsubmit="return confirm('確定要刪除此董事資料？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>姓名</th><td><?= e($director['name']) ?></td>
            <th>職稱</th><td><?= e($director['title'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>屆次</th><td><?= e($director['term_no'] !== null ? '第 ' . $director['term_no'] . ' 屆' : '-') ?></td>
            <th>任期</th>
            <td>
                <?= e(roc_date($director['started_on'])) ?>
                <?= $director['status'] === 'former' ? ' ～ ' . e(roc_date($director['ended_on'])) : ' ～ 現在' ?>
            </td>
        </tr>
        <tr>
            <th>服務單位</th><td><?= e($director['organization'] ?: '-') ?></td>
            <th>單位職稱</th><td><?= e($director['job_title'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>電話</th><td><?= e($director['phone'] ?: '-') ?></td>
            <th>手機</th><td><?= e($director['mobile'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>Email</th><td colspan="3"><?= e($director['email'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>地址</th><td colspan="3"><?= e($director['address'] ?: '-') ?></td>
        </tr>
        <?php if (!empty($director['notes'])): ?>
        <tr>
            <th>備註</th><td colspan="3"><?= nl2br(e($director['notes'])) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="no-print">
            <th>建立人</th><td><?= e($director['created_by_name'] ?? '-') ?></td>
            <th>更新時間</th><td><?= e($director['updated_at'] ? substr((string) $director['updated_at'], 0, 16) : '-') ?></td>
        </tr>
        </tbody>
    </table>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
