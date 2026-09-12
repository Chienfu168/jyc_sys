<?php
$active = 'rewards';
$rewards = $rewards ?? [];
$statusColors = ['draft' => '#6b7280', 'submitted' => '#9a6a00', 'approved' => '#1b7a43', 'rejected' => '#b32d2d'];
$statusLabels = ['draft' => '草稿', 'submitted' => '待核定', 'approved' => '已核定', 'rejected' => '已退回'];
$canEditRow = static function (array $r) use ($canApprove): bool {
    return in_array($r['status'], ['draft', 'rejected'], true) || !empty($canApprove);
};
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>員工獎勵申請</h2>
            <p class="muted-text">依「員工專案及創新成果獎勵辦法」提出專案成果獎勵金申請,送出後由主管核定,可列印申請書用印陳核。</p>
        </div>
        <div class="actions">
            <?php if (!empty($canManage)): ?>
                <a class="btn primary" href="/rewards/create">新增申請</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>單號</th>
                <th>申請日期</th>
                <th>申請項目</th>
                <th>申請人</th>
                <th style="text-align:right">獎勵總額</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rewards as $r): ?>
                <tr>
                    <td><?= e($r['application_no']) ?></td>
                    <td><?= e(roc_date($r['apply_date'])) ?></td>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e($r['applicant_name'] ?? '-') ?></td>
                    <td style="text-align:right"><?= e(number_format((float) $r['total_amount'])) ?></td>
                    <td><span style="color:<?= e($statusColors[$r['status']] ?? '#333') ?>;font-weight:600"><?= e($statusLabels[$r['status']] ?? $r['status']) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/rewards/<?= e((string) $r['id']) ?>">檢視</a>
                        <?php if (!empty($canManage) && $canEditRow($r)): ?>
                            <a class="btn small" href="/rewards/<?= e((string) $r['id']) ?>/edit">編輯</a>
                            <form method="post" action="/rewards/<?= e((string) $r['id']) ?>/delete" onsubmit="return confirm('確定要刪除此獎勵申請？此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rewards): ?>
                <tr><td colspan="7" class="empty">尚無獎勵申請。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
