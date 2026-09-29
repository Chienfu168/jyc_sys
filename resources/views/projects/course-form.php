<?php
$active = 'projects';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($title) ?></h2>
            <p class="muted-text"><?= e($project['name']) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/projects/<?= e((string) $project['id']) ?>">返回專案</a>
        </div>
    </div>

    <form class="form grid-form" method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <label>
            <span>學校</span>
            <input type="text" name="school_name" value="<?= e((string) old('school_name', $course['school_name'] ?? '')) ?>" required>
        </label>
        <label>
            <span>校長姓名<small class="field-hint">（若未確認可留空）</small></span>
            <input type="text" name="principal_name" value="<?= e((string) old('principal_name', $course['principal_name'] ?? '')) ?>">
        </label>
        <label>
            <span>負責主任<small class="field-hint">（若未確認可留空）</small></span>
            <input type="text" name="director_name" value="<?= e((string) old('director_name', $course['director_name'] ?? '')) ?>">
        </label>
        <label>
            <span>課程名稱</span>
            <input type="text" name="course_name" value="<?= e((string) old('course_name', $course['course_name'] ?? '')) ?>" required>
        </label>
        <label>
            <span>授課老師<small class="field-hint">（若未確認可留空）</small></span>
            <input type="text" name="teacher_name" value="<?= e((string) old('teacher_name', $course['teacher_name'] ?? '')) ?>">
        </label>
        <label>
            <span>每週幾<small class="field-hint">（如：星期三；若未確認可留空）</small></span>
            <input type="text" name="weekday" value="<?= e((string) old('weekday', $course['weekday'] ?? '')) ?>" placeholder="星期一～星期日">
        </label>
        <label>
            <span>學年度＋學期</span>
            <input type="text" name="semester_label" value="<?= e((string) old('semester_label', $course['semester_label'] ?? '')) ?>" placeholder="例：115學年度第1學期" required>
        </label>
        <label class="span-2">
            <span>備註</span>
            <textarea name="notes"><?= e((string) old('notes', $course['notes'] ?? '')) ?></textarea>
        </label>
        <div class="form-actions span-2">
            <a class="btn" href="/projects/<?= e((string) $project['id']) ?>">返回</a>
            <button class="btn primary" type="submit">儲存</button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
