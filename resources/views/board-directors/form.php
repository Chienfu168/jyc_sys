<?php
$active = 'board-directors';
$director = $director ?? [];
$titles = $titles ?? [];
$isEdit = !empty($director['id']);
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <div>
            <h2><?= $isEdit ? '編輯董事' : '新增董事' ?></h2>
            <p class="muted-text">董事名冊資料，與各次董事會議的出列席紀錄分開維護。</p>
        </div>
        <a class="btn" href="/board-directors">返回董事名冊</a>
    </div>

    <form method="post" action="<?= e($action) ?>" class="form">
        <?= csrf_field() ?>

        <div class="grid-form">
            <label>
                <span>姓名 <span style="color:#b32d2d">*</span></span>
                <input type="text" name="name" maxlength="120" required value="<?= e((string) old('name', $director['name'] ?? '')) ?>">
            </label>
            <label>
                <span>職稱</span>
                <input type="text" name="title" maxlength="60" list="directorTitles" placeholder="例如：董事長、董事" value="<?= e((string) old('title', $director['title'] ?? '')) ?>">
                <datalist id="directorTitles">
                    <?php foreach ($titles as $t): ?>
                        <option value="<?= e($t) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </label>
            <label>
                <span>屆次<small class="field-hint">（第幾屆董事，選填）</small></span>
                <input type="number" name="term_no" min="1" step="1" value="<?= e((string) old('term_no', $director['term_no'] ?? '')) ?>">
            </label>
            <label>
                <span>服務單位</span>
                <input type="text" name="organization" maxlength="160" value="<?= e((string) old('organization', $director['organization'] ?? '')) ?>">
            </label>
            <label>
                <span>單位職稱</span>
                <input type="text" name="job_title" maxlength="120" placeholder="例如：校長" value="<?= e((string) old('job_title', $director['job_title'] ?? '')) ?>">
            </label>
            <label>
                <span>電話</span>
                <input type="text" name="phone" maxlength="60" value="<?= e((string) old('phone', $director['phone'] ?? '')) ?>">
            </label>
            <label>
                <span>手機</span>
                <input type="text" name="mobile" maxlength="60" value="<?= e((string) old('mobile', $director['mobile'] ?? '')) ?>">
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" maxlength="190" value="<?= e((string) old('email', $director['email'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>地址<small class="field-hint">（供列印標籤等用途）</small></span>
                <input type="text" name="address" maxlength="255" value="<?= e((string) old('address', $director['address'] ?? '')) ?>">
            </label>
            <label>
                <span>狀態</span>
                <?php $st = (string) old('status', $director['status'] ?? 'current'); ?>
                <select name="status">
                    <option value="current" <?= $st !== 'former' ? 'selected' : '' ?>>現任</option>
                    <option value="former" <?= $st === 'former' ? 'selected' : '' ?>>前任</option>
                </select>
            </label>
            <label>
                <span>到任日期</span>
                <input type="date" name="started_on" value="<?= e((string) old('started_on', $director['started_on'] ?? '')) ?>">
            </label>
            <label>
                <span>卸任日期<small class="field-hint">（標示為前任且留空時，自動帶入今天）</small></span>
                <input type="date" name="ended_on" value="<?= e((string) old('ended_on', $director['ended_on'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes" rows="3"><?= e((string) old('notes', $director['notes'] ?? '')) ?></textarea>
            </label>
        </div>

        <div class="form-actions">
            <a class="btn" href="/board-directors">取消</a>
            <button class="btn primary" type="submit"><?= $isEdit ? '儲存變更' : '建立董事' ?></button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
