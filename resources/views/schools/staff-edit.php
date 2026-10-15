<?php
$active = 'schools';
$school = $school ?? [];
$staff = $staff ?? [];
$titles = $titles ?? [];
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <div>
            <h2>編輯人員資料</h2>
            <p class="muted-text"><?= e($school['name']) ?></p>
        </div>
        <a class="btn" href="/schools/<?= e((string) $school['id']) ?>">返回學校資料</a>
    </div>

    <form method="post" action="<?= e($action) ?>" class="form">
        <?= csrf_field() ?>

        <div class="grid-form">
            <label>
                <span>職稱 <span style="color:#b32d2d">*</span></span>
                <input type="text" name="title" maxlength="60" list="schoolStaffTitles" required value="<?= e((string) old('title', $staff['title'] ?? '')) ?>">
                <datalist id="schoolStaffTitles">
                    <?php foreach ($titles as $t): ?>
                        <option value="<?= e($t) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </label>
            <label>
                <span>姓名 <span style="color:#b32d2d">*</span></span>
                <input type="text" name="name" maxlength="120" required value="<?= e((string) old('name', $staff['name'] ?? '')) ?>">
            </label>
            <label>
                <span>電話</span>
                <input type="text" name="phone" maxlength="60" value="<?= e((string) old('phone', $staff['phone'] ?? '')) ?>">
            </label>
            <label>
                <span>手機</span>
                <input type="text" name="mobile" maxlength="60" value="<?= e((string) old('mobile', $staff['mobile'] ?? '')) ?>">
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" maxlength="190" value="<?= e((string) old('email', $staff['email'] ?? '')) ?>">
            </label>
            <label>
                <span>狀態</span>
                <?php $st = (string) old('status', $staff['status'] ?? 'current'); ?>
                <select name="status">
                    <option value="current" <?= $st !== 'former' ? 'selected' : '' ?>>現任</option>
                    <option value="former" <?= $st === 'former' ? 'selected' : '' ?>>前任</option>
                </select>
            </label>
            <label>
                <span>到任日期</span>
                <input type="date" name="started_on" value="<?= e((string) old('started_on', $staff['started_on'] ?? '')) ?>">
            </label>
            <label>
                <span>卸任日期<small class="field-hint">（標示為前任且留空時，自動帶入今天）</small></span>
                <input type="date" name="ended_on" value="<?= e((string) old('ended_on', $staff['ended_on'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes" rows="3"><?= e((string) old('notes', $staff['notes'] ?? '')) ?></textarea>
            </label>
        </div>

        <div class="form-actions">
            <a class="btn" href="/schools/<?= e((string) $school['id']) ?>">取消</a>
            <button class="btn primary" type="submit">儲存變更</button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
