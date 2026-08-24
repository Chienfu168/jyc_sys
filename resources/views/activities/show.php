<?php
$active = 'activities';
$documentTitle = '活動資料表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($activity['title']) ?></h2>
            <p class="muted-text"><?= e(activity_show_datetime($activity['starts_at'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/activities">返回列表</a>
            <?php if (\App\Core\Permission::can('activities.manage')): ?>
                <a class="btn" href="/activities/<?= e((string) $activity['id']) ?>/edit">編輯</a>
                <form method="post" action="/activities/<?= e((string) $activity['id']) ?>/status">
                    <?= csrf_field() ?>
                    <select name="status">
                        <option value="draft" <?= $activity['status'] === 'draft' ? 'selected' : '' ?>>草稿</option>
                        <option value="published" <?= $activity['status'] === 'published' ? 'selected' : '' ?>>已發布</option>
                        <option value="closed" <?= $activity['status'] === 'closed' ? 'selected' : '' ?>>結案</option>
                        <option value="cancelled" <?= $activity['status'] === 'cancelled' ? 'selected' : '' ?>>取消</option>
                    </select>
                    <button class="btn" type="submit">更新狀態</button>
                </form>
                <form method="post" action="/activities/<?= e((string) $activity['id']) ?>/delete" onsubmit="return confirm('確定要刪除此活動？將一併刪除報名、成果與附件紀錄，此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>活動名稱</th>
            <td colspan="3"><?= e($activity['title']) ?></td>
        </tr>
        <tr>
            <th>開始時間</th>
            <td><?= e(activity_show_datetime($activity['starts_at'])) ?></td>
            <th>結束時間</th>
            <td><?= e(activity_show_datetime($activity['ends_at'])) ?></td>
        </tr>
        <tr>
            <th>地點</th>
            <td><?= e($activity['location'] ?: '-') ?></td>
            <th>狀態</th>
            <td><?= e(activity_show_status_label($activity['status'])) ?></td>
        </tr>
        <tr>
            <th>所屬專案</th>
            <td>
                <?php if (!empty($activity['project_id'])): ?>
                    <a class="text-link" href="/projects/<?= e((string) $activity['project_id']) ?>">
                        <?= e(($activity['project_code'] ? $activity['project_code'] . ' / ' : '') . $activity['project_name']) ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <th>行事曆連動</th>
            <td>
                <?php if ($calendarEvent): ?>
                    <a class="text-link" href="/calendar/<?= e((string) $calendarEvent['id']) ?>">已建立行事曆事件</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>建檔人</th>
            <td><?= e($activity['created_by_name'] ?: '-') ?></td>
            <th>更新時間</th>
            <td><?= e($activity['updated_at'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>活動說明</th>
            <td colspan="3"><?= nl2br(e($activity['description'] ?: '-')) ?></td>
        </tr>
        </tbody>
    </table>

    <section class="stats-grid budget-summary no-print">
        <div class="stat-card">
            <span>報名人數</span>
            <strong><?= e(number_format(count($participants))) ?></strong>
        </div>
        <div class="stat-card">
            <span>實際出席</span>
            <strong><?= e(number_format(count(array_filter($participants, static fn (array $participant): bool => $participant['status'] === 'attended')))) ?></strong>
        </div>
        <div class="stat-card">
            <span>成果人數</span>
            <strong><?= e($outcome['actual_participants'] !== '' ? number_format((int) $outcome['actual_participants']) : '-') ?></strong>
        </div>
    </section>

    <?php if (\App\Core\Permission::can('activities.manage')): ?>
        <section class="no-print activity-workbench">
            <div class="feature-card">
                <span>報名名冊</span>
                <strong>新增參加者</strong>
                <form class="form grid-form compact-form" method="post" action="/activities/<?= e((string) $activity['id']) ?>/participants">
                    <?= csrf_field() ?>
                    <label>
                        <span>姓名</span>
                        <input type="text" name="name" value="<?= e((string) old('name')) ?>" required>
                    </label>
                    <label>
                        <span>電話</span>
                        <input type="text" name="phone" value="<?= e((string) old('phone')) ?>">
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?= e((string) old('email')) ?>">
                    </label>
                    <label>
                        <span>單位</span>
                        <input type="text" name="organization" value="<?= e((string) old('organization')) ?>">
                    </label>
                    <label>
                        <span>狀態</span>
                        <?php $participantStatus = old('status', 'registered'); ?>
                        <select name="status">
                            <option value="registered" <?= $participantStatus === 'registered' ? 'selected' : '' ?>>已報名</option>
                            <option value="attended" <?= $participantStatus === 'attended' ? 'selected' : '' ?>>已出席</option>
                            <option value="absent" <?= $participantStatus === 'absent' ? 'selected' : '' ?>>未出席</option>
                            <option value="cancelled" <?= $participantStatus === 'cancelled' ? 'selected' : '' ?>>取消</option>
                        </select>
                    </label>
                    <label>
                        <span>備註</span>
                        <input type="text" name="notes" value="<?= e((string) old('notes')) ?>">
                    </label>
                    <div class="form-actions span-2">
                        <button class="btn primary" type="submit">加入名冊</button>
                    </div>
                </form>
            </div>

            <div class="feature-card">
                <span>成果紀錄</span>
                <strong>活動成果摘要</strong>
                <form class="form grid-form compact-form" method="post" action="/activities/<?= e((string) $activity['id']) ?>/outcome">
                    <?= csrf_field() ?>
                    <label>
                        <span>實際參與人數</span>
                        <input type="number" min="0" step="1" name="actual_participants" value="<?= e((string) old('actual_participants', $outcome['actual_participants'] ?? '')) ?>">
                    </label>
                    <label>
                        <span>滿意度 0-5</span>
                        <input type="number" min="0" max="5" step="0.01" name="satisfaction_score" value="<?= e((string) old('satisfaction_score', $outcome['satisfaction_score'] ?? '')) ?>">
                    </label>
                    <label class="span-2">
                        <span>成果摘要</span>
                        <textarea name="outcome_summary"><?= e((string) old('outcome_summary', $outcome['outcome_summary'] ?? '')) ?></textarea>
                    </label>
                    <label class="span-2">
                        <span>檢討與改善</span>
                        <textarea name="improvement_notes"><?= e((string) old('improvement_notes', $outcome['improvement_notes'] ?? '')) ?></textarea>
                    </label>
                    <div class="form-actions span-2">
                        <button class="btn primary" type="submit">儲存成果</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="no-print">
            <div class="feature-card">
                <span>成果附件</span>
                <strong>上傳照片、簽到表或成果文件</strong>
                <form class="form grid-form compact-form" method="post" action="/activities/<?= e((string) $activity['id']) ?>/attachments" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label>
                        <span>附件類型</span>
                        <select name="category">
                            <option value="report">成果報告</option>
                            <option value="attendance">簽到表</option>
                            <option value="photo">活動照片</option>
                            <option value="receipt">核銷附件</option>
                            <option value="other">其他</option>
                        </select>
                    </label>
                    <label>
                        <span>附件標題</span>
                        <input type="text" name="title" value="<?= e((string) old('title')) ?>" placeholder="未填則使用原始檔名">
                    </label>
                    <label class="span-2">
                        <span>選擇檔案</span>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.txt" required>
                    </label>
                    <label class="span-2">
                        <span>備註</span>
                        <input type="text" name="notes" value="<?= e((string) old('notes')) ?>">
                    </label>
                    <div class="form-actions span-2">
                        <button class="btn primary" type="submit">上傳附件</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>參加者</th>
                <th>聯絡方式</th>
                <th>單位</th>
                <th>狀態</th>
                <th>簽到時間</th>
                <th>備註</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($participants as $participant): ?>
                <tr>
                    <td><strong><?= e($participant['name']) ?></strong></td>
                    <td>
                        <?= e($participant['phone'] ?: '-') ?>
                        <?php if (!empty($participant['email'])): ?>
                            <div class="muted-text"><?= e($participant['email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($participant['organization'] ?: '-') ?></td>
                    <td><?= e(activity_participant_status_label($participant['status'])) ?></td>
                    <td><?= e(activity_show_datetime($participant['checked_in_at'])) ?></td>
                    <td><?= e($participant['notes'] ?: '-') ?></td>
                    <td class="actions no-print">
                        <?php if (\App\Core\Permission::can('activities.manage')): ?>
                            <form method="post" action="/activities/<?= e((string) $activity['id']) ?>/participants/<?= e((string) $participant['id']) ?>/status">
                                <?= csrf_field() ?>
                                <select name="status">
                                    <option value="registered" <?= $participant['status'] === 'registered' ? 'selected' : '' ?>>已報名</option>
                                    <option value="attended" <?= $participant['status'] === 'attended' ? 'selected' : '' ?>>已出席</option>
                                    <option value="absent" <?= $participant['status'] === 'absent' ? 'selected' : '' ?>>未出席</option>
                                    <option value="cancelled" <?= $participant['status'] === 'cancelled' ? 'selected' : '' ?>>取消</option>
                                </select>
                                <button class="btn small" type="submit">更新</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$participants): ?>
                <tr><td colspan="7" class="empty">尚無報名或簽到資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>實際參與人數</th>
            <td><?= e($outcome['actual_participants'] !== '' ? number_format((int) $outcome['actual_participants']) : '-') ?></td>
            <th>滿意度</th>
            <td><?= e($outcome['satisfaction_score'] !== '' ? number_format((float) $outcome['satisfaction_score'], 2) : '-') ?></td>
        </tr>
        <tr>
            <th>成果摘要</th>
            <td colspan="3"><?= nl2br(e($outcome['outcome_summary'] ?: '-')) ?></td>
        </tr>
        <tr>
            <th>檢討與改善</th>
            <td colspan="3"><?= nl2br(e($outcome['improvement_notes'] ?: '-')) ?></td>
        </tr>
        <tr>
            <th>成果更新人</th>
            <td><?= e($outcome['updated_by_name'] ?: '-') ?></td>
            <th>更新時間</th>
            <td><?= e($outcome['updated_at'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>附件類型</th>
                <th>標題</th>
                <th>檔名</th>
                <th class="amount">大小</th>
                <th>上傳人</th>
                <th>上傳時間</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($attachments as $attachment): ?>
                <tr>
                    <td><?= e(activity_attachment_category_label($attachment['category'])) ?></td>
                    <td>
                        <strong><?= e($attachment['title']) ?></strong>
                        <?php if (!empty($attachment['notes'])): ?>
                            <div class="muted-text"><?= e($attachment['notes']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($attachment['original_name']) ?></td>
                    <td class="amount"><?= e(activity_file_size((int) $attachment['file_size'])) ?></td>
                    <td><?= e($attachment['uploaded_by_name'] ?: '-') ?></td>
                    <td><?= e($attachment['created_at'] ?: '-') ?></td>
                    <td class="actions no-print">
                        <a class="btn small" href="/activities/<?= e((string) $activity['id']) ?>/attachments/<?= e((string) $attachment['id']) ?>/download">下載</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$attachments): ?>
                <tr><td colspan="7" class="empty">尚無活動附件。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>志工</th>
                <th>服務日期</th>
                <th>服務內容</th>
                <th class="amount">時數</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($volunteerLogs as $log): ?>
                <tr>
                    <td><?= e($log['volunteer_name']) ?></td>
                    <td><?= e(roc_date($log['served_on'])) ?></td>
                    <td><?= e($log['description'] ?: '-') ?></td>
                    <td class="amount"><?= e(number_format((float) $log['hours'], 2)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$volunteerLogs): ?>
                <tr><td colspan="4" class="empty">尚無志工服務紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="3">志工時數合計</th>
                <th class="amount"><?= e(number_format(array_sum(array_map(static fn (array $log): float => (float) $log['hours'], $volunteerLogs)), 2)) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function activity_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'published' => '已發布', 'closed' => '結案', 'cancelled' => '取消'][$status] ?? $status;
}
function activity_participant_status_label(string $status): string
{
    return ['registered' => '已報名', 'attended' => '已出席', 'absent' => '未出席', 'cancelled' => '取消'][$status] ?? $status;
}
function activity_attachment_category_label(string $category): string
{
    return ['photo' => '活動照片', 'attendance' => '簽到表', 'receipt' => '核銷附件', 'report' => '成果報告', 'other' => '其他'][$category] ?? $category;
}
function activity_file_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return number_format($bytes) . ' B';
}
function activity_show_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    return roc_date(substr($value, 0, 10)) . ' ' . substr($value, 11, 5);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
