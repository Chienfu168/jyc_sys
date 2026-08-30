<?php
$active = 'quick-links';
$groups = $groups ?? [];
$selectedKeys = $selectedKeys ?? [];
$orderIndex = array_flip($selectedKeys); // key => 目前順序
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>常用連結設定</h2>
            <p class="muted-text">勾選您最常操作的功能,會顯示在左側選單最上方的「常用連結」區,方便快速進入。此設定僅屬於您個人。</p>
        </div>
    </div>

    <form method="post" action="/quick-links" class="form">
        <?= csrf_field() ?>

        <p class="field-hint">「順序」數字越小越靠前(留白則排在最後,依清單順序)。只會顯示您有權限使用的功能。</p>

        <?php foreach ($groups as $group): ?>
            <div class="form-section">
                <h3><?= e($group['title']) ?></h3>
                <div class="ql-grid">
                    <?php foreach ($group['items'] as $item): ?>
                        <?php $checked = isset($orderIndex[$item['key']]); ?>
                        <label class="ql-option<?= $checked ? ' is-checked' : '' ?>">
                            <input type="checkbox" name="links[]" value="<?= e($item['key']) ?>" <?= $checked ? 'checked' : '' ?>>
                            <span class="ql-option__icon"><?= e($item['icon']) ?></span>
                            <span class="ql-option__label"><?= e($item['label']) ?></span>
                            <input type="number" class="ql-option__order" name="order[<?= e($item['key']) ?>]" min="1" max="99"
                                   value="<?= $checked ? e((string) ($orderIndex[$item['key']] + 1)) : '' ?>"
                                   placeholder="順序" aria-label="排序" inputmode="numeric">
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <button class="btn primary" type="submit">儲存常用連結</button>
        </div>
    </form>
</section>

<script>
(function () {
    // 勾選狀態即時反映外觀,並在取消勾選時清空順序。
    Array.prototype.forEach.call(document.querySelectorAll('.ql-option'), function (opt) {
        var cb = opt.querySelector('input[type="checkbox"]');
        var order = opt.querySelector('.ql-option__order');
        if (!cb) return;
        cb.addEventListener('change', function () {
            opt.classList.toggle('is-checked', cb.checked);
            if (!cb.checked && order) { order.value = ''; }
        });
        // 點順序欄不應切換勾選。
        if (order) {
            order.addEventListener('click', function (e) { e.preventDefault(); });
            order.addEventListener('input', function () {
                if (order.value && !cb.checked) { cb.checked = true; opt.classList.add('is-checked'); }
            });
        }
    });
})();
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
