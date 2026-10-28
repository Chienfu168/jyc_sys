<?php
$active = 'charters';
$charter = $charter ?? [];
$isEdit = !empty($charter['id']);
$documentTitle = $isEdit ? '編輯章程版本' : '新增章程版本';
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <div>
            <h2><?= $isEdit ? '編輯章程版本' : '新增章程版本' ?></h2>
            <p class="muted-text">記錄基金會章程的訂定／修訂內容，設為「現行」時會自動將原現行版本改為歷史。</p>
        </div>
        <a class="btn" href="/charters">返回章程名冊</a>
    </div>

    <form method="post" action="<?= e($action) ?>" class="form" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="grid-form">
            <label class="span-2">
                <span>章程名稱 <span style="color:#b32d2d">*</span></span>
                <input type="text" name="title" maxlength="160" required value="<?= e((string) old('title', $charter['title'] ?? '')) ?>">
            </label>
            <label>
                <span>修訂版次<small class="field-hint">（選填，供排序與辨識）</small></span>
                <input type="number" name="revision_no" min="1" step="1" value="<?= e((string) old('revision_no', $charter['revision_no'] ?? '')) ?>">
            </label>
            <label>
                <span>狀態</span>
                <?php $st = (string) old('status', $charter['status'] ?? 'historical'); ?>
                <select name="status">
                    <option value="historical" <?= $st !== 'current' ? 'selected' : '' ?>>歷史</option>
                    <option value="current" <?= $st === 'current' ? 'selected' : '' ?>>現行（將自動取代原現行版本）</option>
                </select>
            </label>
            <label>
                <span>訂定／異動日期</span>
                <input type="date" name="enacted_on" value="<?= e((string) old('enacted_on', $charter['enacted_on'] ?? '')) ?>">
            </label>
            <label>
                <span>核備機關</span>
                <input type="text" name="approval_authority" maxlength="160" value="<?= e((string) old('approval_authority', $charter['approval_authority'] ?? '')) ?>">
            </label>
            <label>
                <span>核准文號</span>
                <input type="text" name="approval_doc_no" maxlength="120" value="<?= e((string) old('approval_doc_no', $charter['approval_doc_no'] ?? '')) ?>">
            </label>
            <label>
                <span>核准日期</span>
                <input type="date" name="approval_date" value="<?= e((string) old('approval_date', $charter['approval_date'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>修訂重點說明</span>
                <textarea name="amendment_summary" rows="3"><?= e((string) old('amendment_summary', $charter['amendment_summary'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>章程全文<small class="field-hint">（選填，供查閱／搜尋；正式核備文件請上傳檔案）</small></span>
                <textarea name="content" rows="8"><?= e((string) old('content', $charter['content'] ?? '')) ?></textarea>
            </label>
            <label class="span-2 logo-field">
                <span class="logo-field__label">核備章程檔案</span>
                <?php if (!empty($charter['stored_path'])): ?>
                    <p class="muted-text">
                        目前檔案：<a href="/charters/<?= e((string) $charter['id']) ?>/file"><?= e((string) $charter['original_name']) ?></a>
                    </p>
                    <label class="logo-field__remove">
                        <input type="checkbox" name="remove_file" value="1">
                        <span>移除目前檔案</span>
                    </label>
                <?php endif; ?>
                <input type="file" name="file" accept="application/pdf,image/png,image/jpeg,image/gif,image/webp">
                <small class="logo-field__hint">支援 PDF 或圖片（JPG／PNG／GIF／WEBP），檔案 15MB 以內。選擇新檔案將取代原檔案。</small>
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes" rows="3"><?= e((string) old('notes', $charter['notes'] ?? '')) ?></textarea>
            </label>
        </div>

        <div class="form-actions">
            <a class="btn" href="/charters">取消</a>
            <button class="btn primary" type="submit"><?= $isEdit ? '儲存變更' : '建立章程版本' ?></button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
