<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>專案代碼</span>
        <input type="text" name="project_code" value="<?= e((string) old('project_code', $project['project_code'] ?? '')) ?>" placeholder="例：PRJ-115-001">
    </label>
    <label>
        <span>專案類型</span>
        <?php $type = old('project_type', $project['project_type'] ?? 'program'); ?>
        <select name="project_type" required>
            <option value="program" <?= $type === 'program' ? 'selected' : '' ?>>服務方案</option>
            <option value="grant" <?= $type === 'grant' ? 'selected' : '' ?>>補助計畫</option>
            <option value="administration" <?= $type === 'administration' ? 'selected' : '' ?>>行政專案</option>
            <option value="event" <?= $type === 'event' ? 'selected' : '' ?>>活動專案</option>
            <option value="other" <?= $type === 'other' ? 'selected' : '' ?>>其他</option>
        </select>
    </label>
    <label class="span-2">
        <span>專案名稱</span>
        <input type="text" name="name" value="<?= e((string) old('name', $project['name'] ?? '')) ?>" required>
    </label>
    <label>
        <span>承辦人</span>
        <input type="text" name="owner_name" value="<?= e((string) old('owner_name', $project['owner_name'] ?? '')) ?>">
    </label>
    <label>
        <span>部門</span>
        <input type="text" name="department" list="project-departments" value="<?= e((string) old('department', $project['department'] ?? '')) ?>">
        <datalist id="project-departments">
            <option value="行政管理組"></option>
            <option value="教育推廣組"></option>
            <option value="財務會計組"></option>
            <option value="社區合作組"></option>
        </datalist>
    </label>
    <label>
        <span>經費來源</span>
        <input type="text" name="funding_source" list="project-funding-sources" value="<?= e((string) old('funding_source', $project['funding_source'] ?? '')) ?>">
        <datalist id="project-funding-sources">
            <option value="年度預算"></option>
            <option value="指定捐款"></option>
            <option value="政府補助"></option>
            <option value="行政管理費"></option>
        </datalist>
    </label>
    <label>
        <span>預算金額</span>
        <input type="number" min="0" step="1" name="budget_amount" value="<?= e((string) old('budget_amount', $project['budget_amount'] ?? '')) ?>">
    </label>
    <label>
        <span>開始日期</span>
        <input type="date" name="start_date" value="<?= e((string) old('start_date', $project['start_date'] ?? '')) ?>">
    </label>
    <label>
        <span>結束日期</span>
        <input type="date" name="end_date" value="<?= e((string) old('end_date', $project['end_date'] ?? '')) ?>">
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $project['status'] ?? 'planning'); ?>
        <select name="status" required>
            <option value="planning" <?= $status === 'planning' ? 'selected' : '' ?>>規劃中</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>執行中</option>
            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>已結案</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>已取消</option>
        </select>
    </label>
    <label class="span-2">
        <span>專案目的</span>
        <textarea name="purpose"><?= e((string) old('purpose', $project['purpose'] ?? '')) ?></textarea>
    </label>
    <label class="span-2">
        <span>預期成果</span>
        <textarea name="expected_outcome"><?= e((string) old('expected_outcome', $project['expected_outcome'] ?? '')) ?></textarea>
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $project['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/projects">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
