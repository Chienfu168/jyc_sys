<div class="section-title">
    <h3>簽核流程</h3>
    <p class="muted-text">送審後由具核准權限的人員核准或退回。</p>
</div>

<?php if (!empty($approvalCanApprove) && ($approvalStatus ?? '') === 'submitted'): ?>
    <form class="form approval-form no-print" method="post" action="<?= e($approvalApproveUrl) ?>">
        <?= csrf_field() ?>
        <label>
            <span>核准備註</span>
            <textarea name="review_notes" rows="2"></textarea>
        </label>
        <div class="form-actions">
            <button class="btn primary" type="submit">核准</button>
            <button class="btn" type="submit" formaction="<?= e($approvalRejectUrl) ?>">退回</button>
        </div>
    </form>
<?php endif; ?>

<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>時間</th>
            <th>動作</th>
            <th>狀態</th>
            <th>送審人</th>
            <th>核決人</th>
            <th>備註</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($approvalHistory ?? []) as $history): ?>
            <tr>
                <td><?= e(shared_approval_datetime($history['created_at'] ?? '')) ?></td>
                <td><?= e(shared_approval_action((string) $history['action'])) ?></td>
                <td><?= e(shared_approval_status((string) $history['status'])) ?></td>
                <td><?= e($history['requested_by_name'] ?? '-') ?></td>
                <td><?= e($history['reviewed_by_name'] ?? '-') ?></td>
                <td><?= e($history['review_notes'] ?: ($history['request_notes'] ?: '-')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($approvalHistory)): ?>
            <tr><td colspan="6" class="empty-state">尚無簽核紀錄</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
if (!function_exists('shared_approval_action')) {
    function shared_approval_action(string $action): string
    {
        return ['submit' => '送審', 'approved' => '核准', 'rejected' => '退回'][$action] ?? $action;
    }
}

if (!function_exists('shared_approval_status')) {
    function shared_approval_status(string $status): string
    {
        return ['pending' => '待審', 'approved' => '已核准', 'rejected' => '已退回', 'cancelled' => '已取消'][$status] ?? $status;
    }
}

if (!function_exists('shared_approval_datetime')) {
    function shared_approval_datetime(?string $datetime): string
    {
        if (!$datetime) {
            return '-';
        }

        $date = substr($datetime, 0, 10);
        $time = substr($datetime, 11, 5);

        return roc_date($date) . ($time ? ' ' . $time : '');
    }
}
