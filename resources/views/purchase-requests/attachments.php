<?php $attachments = $attachments ?? []; ?>

<section class="panel purchase-attachments no-print">
    <div class="purchase-section-heading">
        <h3>採購附件</h3>
        <p class="muted-text">保存報價單、發票、合約、驗收文件與付款憑證。目前僅接受圖片檔（JPG／PNG／GIF／WEBP），列印採購申請單時會一併印出。</p>
    </div>

    <?php if (!empty($canManage) && ($request['status'] ?? '') !== 'voided'): ?>
        <form class="form grid-form compact-form" method="post" action="/purchase-requests/<?= e((string) $request['id']) ?>/attachments" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label>
                <span>附件類型</span>
                <?php $attachmentCategory = old('category', 'quotation'); ?>
                <select name="category">
                    <?php foreach (purchase_attachment_category_options() as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $attachmentCategory === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>附件標題</span>
                <input type="text" name="title" value="<?= e((string) old('title')) ?>" placeholder="未填則使用原始檔名">
            </label>
            <label class="span-2">
                <span>選擇檔案</span>
                <input type="file" name="attachment" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" capture="environment" required>
                <span class="field-hint">僅接受圖片檔（JPG／PNG／GIF／WEBP）；手機可直接拍照上傳。</span>
            </label>
            <label class="span-2">
                <span>備註</span>
                <input type="text" name="notes" value="<?= e((string) old('notes')) ?>">
            </label>
            <div class="form-actions span-2">
                <button class="btn primary" type="submit">上傳附件</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>類型</th>
                <th>標題</th>
                <th>檔名</th>
                <th class="amount">大小</th>
                <th>上傳人</th>
                <th>上傳時間</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($attachments as $attachment): ?>
                <tr>
                    <td><?= e(purchase_attachment_category_label((string) $attachment['category'])) ?></td>
                    <td>
                        <strong><?= e($attachment['title']) ?></strong>
                        <?php if (!empty($attachment['notes'])): ?>
                            <div class="muted-text"><?= e($attachment['notes']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($attachment['original_name']) ?></td>
                    <td class="amount"><?= e(purchase_attachment_file_size((int) $attachment['file_size'])) ?></td>
                    <td><?= e($attachment['uploaded_by_name'] ?: '-') ?></td>
                    <td><?= e($attachment['created_at'] ?: '-') ?></td>
                    <td class="actions">
                        <a class="btn small" href="/purchase-requests/<?= e((string) $request['id']) ?>/attachments/<?= e((string) $attachment['id']) ?>/download">下載</a>
                        <?php if (!empty($canManage)): ?>
                            <form method="post" action="/purchase-requests/<?= e((string) $request['id']) ?>/attachments/<?= e((string) $attachment['id']) ?>/delete" onsubmit="return confirm('確定要刪除此採購附件？');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$attachments): ?>
                <tr><td colspan="7" class="empty">尚無採購附件。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
function purchase_attachment_category_options(): array
{
    return [
        'quotation' => '報價單',
        'invoice' => '發票 / 收據',
        'contract' => '合約 / 訂單',
        'delivery' => '驗收文件',
        'payment' => '付款憑證',
        'other' => '其他',
    ];
}

function purchase_attachment_category_label(string $category): string
{
    return purchase_attachment_category_options()[$category] ?? $category;
}

function purchase_attachment_file_size(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / 1024 / 1024, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }

    return number_format($bytes) . ' B';
}
?>
