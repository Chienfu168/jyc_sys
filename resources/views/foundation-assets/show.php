<?php
$documentTitle = '財產清冊明細表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($asset['asset_name']) ?></h2>
            <p class="muted-text"><?= e(foundation_asset_registration_label($asset['registration_status'])) ?> / <?= e(foundation_asset_kind_label($asset['asset_kind'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/foundation-assets">返回清冊</a>
            <?php if (\App\Core\Permission::can('foundation_assets.manage')): ?>
                <a class="btn" href="/foundation-assets/<?= e((string) $asset['id']) ?>/edit">編輯</a>
                <form method="post" action="/foundation-assets/<?= e((string) $asset['id']) ?>/dispose">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit"><?= $asset['status'] === 'active' ? '標示除帳' : '恢復使用' ?></button>
                </form>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('foundation_assets.delete') || owns_record($asset['created_by'] ?? null)): ?>
                <form method="post" action="/foundation-assets/<?= e((string) $asset['id']) ?>/delete" onsubmit="return confirm('確定要刪除此財產？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn danger" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr><th>登記狀態</th><td><?= e(foundation_asset_registration_label($asset['registration_status'])) ?></td><th>財產種類</th><td><?= e(foundation_asset_kind_label($asset['asset_kind'])) ?></td></tr>
        <tr><th>名稱</th><td><?= e($asset['asset_name']) ?></td><th>狀態</th><td><?= e($asset['status'] === 'active' ? '使用中' : '已除帳') ?></td></tr>
        <tr><th>單位</th><td><?= e($asset['unit'] ?: '-') ?></td><th>數量</th><td><?= e(number_format((float) $asset['quantity'], 2)) ?></td></tr>
        <tr><th>金額</th><td><?= e(number_format((float) $asset['amount'], 0)) ?></td><th>取得日期</th><td><?= e(roc_date($asset['acquired_on'] ?? null)) ?></td></tr>
        <tr><th>取得來源</th><td><?= e($asset['acquisition_source'] ?: '-') ?></td><th>存放地點</th><td><?= e($asset['storage_location'] ?: '-') ?></td></tr>
        <tr><th>證明文件</th><td><?= e($asset['proof_document'] ?: '-') ?></td><th>財產編號</th><td><?= e($asset['asset_no'] ?: '-') ?></td></tr>
        <tr><th>備註</th><td colspan="3"><?= nl2br(e($asset['notes'] ?: '-')) ?></td></tr>
        </tbody>
    </table>

    <?php $signatureContext = 'foundation-assets'; ?>
    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function foundation_asset_registration_label(string $value): string
{
    return ['court_registered' => '經法院登記', 'not_registered' => '未經法院登記'][$value] ?? $value;
}
function foundation_asset_kind_label(string $value): string
{
    return ['movable' => '動產', 'real_estate' => '不動產', 'other' => '其他'][$value] ?? $value;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
