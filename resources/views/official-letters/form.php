<?php
$active = 'official-letters';
ob_start();
?>
<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>民國年度</span>
                <input type="number" name="fiscal_year" min="1" max="2100" value="<?= e((string) old('fiscal_year', roc_year($letter['fiscal_year'] ?? date('Y')))) ?>" required>
            </label>
            <label>
                <span>發文日期</span>
                <input type="date" name="letter_date" value="<?= e((string) old('letter_date', $letter['letter_date'] ?? date('Y-m-d'))) ?>" required>
            </label>
            <label>
                <span>發文字號</span>
                <input type="text" name="letter_number" value="<?= e((string) old('letter_number', $letter['letter_number'] ?? '')) ?>" placeholder="例：王字第114010101號">
            </label>
            <label>
                <span>速別</span>
                <input type="text" name="urgency" value="<?= e((string) old('urgency', $letter['urgency'] ?? '普通件')) ?>">
            </label>
            <label class="span-2">
                <span>受文者</span>
                <input type="text" name="recipient" value="<?= e((string) old('recipient', $letter['recipient'] ?? '')) ?>" required>
            </label>
            <label>
                <span>密等及解密條件</span>
                <input type="text" name="confidentiality" value="<?= e((string) old('confidentiality', $letter['confidentiality'] ?? '')) ?>">
            </label>
            <label>
                <span>附件</span>
                <input type="text" name="attachment_note" value="<?= e((string) old('attachment_note', $letter['attachment_note'] ?? '如說明三')) ?>">
            </label>
            <label class="span-2">
                <span>主旨</span>
                <textarea name="subject" required><?= e((string) old('subject', $letter['subject'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>說明(一)、(二)... 依據及辦理情形，每行一項</span>
                <textarea name="basis_lines" rows="4"><?= e((string) old('basis_lines', $letter['basis_lines'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>說明最後一項文字(附件清單前言)</span>
                <input type="text" name="attachment_intro" value="<?= e((string) old('attachment_intro', $letter['attachment_intro'] ?? '檢附左列文件各 1 份：')) ?>">
            </label>
            <label class="span-2">
                <span>附件清單(一)(二)... 每行一項</span>
                <textarea name="attachment_items" id="attachment-items" rows="6"><?= e((string) old('attachment_items', $letter['attachment_items'] ?? '')) ?></textarea>
            </label>
            <?php if (!empty($documents)): ?>
                <div class="span-2 doc-picker">
                    <p class="doc-picker-title">快速帶入系統既有文件(點選加入附件清單):</p>
                    <?php
                    $grouped = [];
                    foreach ($documents as $doc) {
                        $grouped[$doc['group']][] = $doc;
                    }
                    ?>
                    <?php foreach ($grouped as $groupName => $docs): ?>
                        <div class="doc-picker-group">
                            <span class="doc-picker-group-name"><?= e($groupName) ?></span>
                            <div class="doc-picker-chips">
                                <?php foreach ($docs as $doc): ?>
                                    <button type="button" class="btn small doc-chip" data-label="<?= e($doc['label']) ?>">＋ <?= e($doc['label']) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <label>
                <span>具名職稱</span>
                <input type="text" name="signer_title" value="<?= e((string) old('signer_title', $letter['signer_title'] ?? '董事長')) ?>">
            </label>
            <label>
                <span>具名姓名</span>
                <input type="text" name="signer_name" value="<?= e((string) old('signer_name', $letter['signer_name'] ?? '')) ?>">
            </label>
            <label>
                <span>狀態</span>
                <?php $status = old('status', $letter['status'] ?? 'draft'); ?>
                <select name="status">
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
                    <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>已發文</option>
                </select>
            </label>
        </div>
    </div>

    <div class="form-actions">
        <a class="btn" href="/official-letters">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
<script>
(function () {
    var textarea = document.getElementById('attachment-items');
    if (!textarea) {
        return;
    }
    document.querySelectorAll('.doc-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var label = chip.getAttribute('data-label') || '';
            if (label === '') {
                return;
            }
            var lines = textarea.value.split(/\r\n|\r|\n/).map(function (line) {
                return line.trim();
            }).filter(function (line) {
                return line !== '';
            });
            if (lines.indexOf(label) !== -1) {
                return;
            }
            lines.push(label);
            textarea.value = lines.join('\n');
        });
    });
})();
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
