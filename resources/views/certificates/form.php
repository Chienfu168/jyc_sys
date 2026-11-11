<?php
$active = 'certificates';
$certificate = $certificate ?? [];
$templates = $templates ?? [];
$preserveBody = $preserveBody ?? false;
$isEdit = !empty($certificate['id']);
$documentTitle = $isEdit ? '編輯聘書／感謝狀' : '開立聘書／感謝狀';
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <div>
            <h2><?= $isEdit ? '編輯聘書／感謝狀' : '開立聘書／感謝狀' ?></h2>
            <p class="muted-text">選擇範本與彈性文字版本後會自動帶入正文，正文框仍可自由編修；方括號［　］為待填內容，請直接於正文框內修改。</p>
        </div>
        <a class="btn" href="/certificates">返回列表</a>
    </div>

    <form method="post" action="<?= e($action) ?>" class="form" id="certificateForm">
        <?= csrf_field() ?>

        <div class="grid-form">
            <label>
                <span>範本類型 <span style="color:#b32d2d">*</span></span>
                <select name="template_key" id="templateKey">
                    <?php foreach ($templates as $key => $tpl): ?>
                        <option value="<?= e($key) ?>" <?= ($certificate['template_key'] ?? '') === $key ? 'selected' : '' ?>><?= e($tpl['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>彈性文字版本</span>
                <select name="variant_key" id="variantKey"></select>
            </label>
            <label>
                <span>外框樣式</span>
                <select name="frame_style">
                    <?php foreach ($frameStyleOptions as $frameStyle): ?>
                        <option value="<?= e($frameStyle['key']) ?>" <?= (old('frame_style', $certificate['frame_style'] ?? 'classic') === $frameStyle['key']) ? 'selected' : '' ?>><?= e($frameStyle['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>姓名／受贈者 <span style="color:#b32d2d">*</span></span>
                <input type="text" name="recipient_name" id="recipientName" maxlength="160" required value="<?= e((string) old('recipient_name', $certificate['recipient_name'] ?? '')) ?>">
            </label>
            <label id="termNoField">
                <span>屆次</span>
                <input type="number" name="term_no" id="termNo" min="1" step="1" value="<?= e((string) old('term_no', $certificate['term_no'] ?? '')) ?>">
            </label>
            <label id="termStartField">
                <span>聘期起</span>
                <input type="date" name="term_start" id="termStart" value="<?= e((string) old('term_start', $certificate['term_start'] ?? '')) ?>">
            </label>
            <label id="termEndField">
                <span>聘期迄</span>
                <input type="date" name="term_end" id="termEnd" value="<?= e((string) old('term_end', $certificate['term_end'] ?? '')) ?>">
                <small class="field-hint" id="termHint"></small>
            </label>
            <label>
                <span>開立日期</span>
                <input type="date" name="issued_on" value="<?= e((string) old('issued_on', $certificate['issued_on'] ?? date('Y-m-d'))) ?>">
            </label>
            <label>
                <span>落款職稱</span>
                <input type="text" name="signer_title" maxlength="60" value="<?= e((string) old('signer_title', $certificate['signer_title'] ?? '董事長')) ?>">
            </label>
            <label>
                <span>落款姓名</span>
                <input type="text" name="signer_name" maxlength="120" value="<?= e((string) old('signer_name', $certificate['signer_name'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>正文
                    <button class="btn small" type="button" id="regenerateBtn" style="margin-left:8px">重新套用範本內容</button>
                </span>
                <textarea name="body_text" id="bodyText" rows="8" required><?= e((string) old('body_text', $certificate['body_text'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes" rows="2"><?= e((string) old('notes', $certificate['notes'] ?? '')) ?></textarea>
            </label>
        </div>

        <div class="form-actions">
            <a class="btn" href="/certificates">取消</a>
            <button class="btn primary" type="submit"><?= $isEdit ? '儲存變更' : '建立文件' ?></button>
        </div>
    </form>
</section>
<script>
(() => {
    const TEMPLATES = <?= json_encode($templates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const currentTemplateKey = <?= json_encode($certificate['template_key'] ?? 'board_director') ?>;
    const currentVariantKey = <?= json_encode($certificate['variant_key'] ?? 'standard') ?>;
    let bodyDirty = <?= $preserveBody ? 'true' : 'false' ?>;

    const templateSelect = document.getElementById('templateKey');
    const variantSelect = document.getElementById('variantKey');
    const nameInput = document.getElementById('recipientName');
    const termNoInput = document.getElementById('termNo');
    const termStartInput = document.getElementById('termStart');
    const termEndInput = document.getElementById('termEnd');
    const bodyTextarea = document.getElementById('bodyText');
    const termNoField = document.getElementById('termNoField');
    const termStartField = document.getElementById('termStartField');
    const termEndField = document.getElementById('termEndField');
    const termHint = document.getElementById('termHint');
    const regenerateBtn = document.getElementById('regenerateBtn');

    function rocPlain(dateStr) {
        const m = (dateStr || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!m) { return null; }
        const y = parseInt(m[1], 10) - 1911;
        return y + ' 年 ' + parseInt(m[2], 10) + ' 月 ' + parseInt(m[3], 10) + ' 日';
    }

    function populateVariants(templateKey, selectedVariant) {
        const tpl = TEMPLATES[templateKey];
        variantSelect.innerHTML = '';
        Object.keys(tpl.variants).forEach(function (key) {
            const opt = document.createElement('option');
            opt.value = key;
            opt.textContent = tpl.variants[key].label;
            if (key === selectedVariant) { opt.selected = true; }
            variantSelect.appendChild(opt);
        });
    }

    function updateFieldVisibility() {
        const tpl = TEMPLATES[templateSelect.value];
        termNoField.style.display = tpl.needs_term_no ? '' : 'none';
        termStartField.style.display = tpl.has_term ? '' : 'none';
        termEndField.style.display = tpl.has_term ? '' : 'none';
        termHint.textContent = tpl.term_hint || '';
        termHint.style.display = tpl.term_hint ? '' : 'none';
    }

    function mergeBody() {
        const tpl = TEMPLATES[templateSelect.value];
        const variant = tpl.variants[variantSelect.value] || tpl.variants[Object.keys(tpl.variants)[0]];
        const termNoVal = termNoInput.value.trim();
        const replacements = {
            '{{name}}': nameInput.value.trim() || '［姓名］',
            '{{term_no}}': termNoVal !== '' ? termNoVal : '［屆次］',
            '{{start_roc}}': rocPlain(termStartInput.value) || '［年］年［月］月［日］日',
            '{{end_roc}}': rocPlain(termEndInput.value) || '［年］年［月］月［日］日',
        };
        let body = variant.body;
        Object.keys(replacements).forEach(function (key) {
            body = body.split(key).join(replacements[key]);
        });
        if (tpl.closing_fixed) {
            body += '\n\n' + tpl.closing_fixed;
        }
        return body;
    }

    function regenerate() {
        bodyTextarea.value = mergeBody();
    }

    function onFieldChange() {
        if (!bodyDirty) { regenerate(); }
    }

    templateSelect.addEventListener('change', function () {
        populateVariants(templateSelect.value, 'standard');
        updateFieldVisibility();
        onFieldChange();
    });
    variantSelect.addEventListener('change', onFieldChange);
    nameInput.addEventListener('input', onFieldChange);
    termNoInput.addEventListener('input', onFieldChange);
    termStartInput.addEventListener('input', onFieldChange);
    termEndInput.addEventListener('input', onFieldChange);

    bodyTextarea.addEventListener('input', function () { bodyDirty = true; });

    regenerateBtn.addEventListener('click', function () {
        if (bodyDirty && !confirm('正文框內容將被範本內容覆蓋，確定要重新套用嗎？')) {
            return;
        }
        bodyDirty = false;
        regenerate();
    });

    populateVariants(currentTemplateKey, currentVariantKey);
    updateFieldVisibility();
})();
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
