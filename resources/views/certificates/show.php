<?php
$active = 'certificates';
$certificate = $certificate ?? [];
$documentTitle = $certificate['title'] . ' — ' . $certificate['recipient_name'];
ob_start();
?>
<section class="panel no-print">
    <div class="panel-header">
        <div>
            <h2><?= e($certificate['title']) ?>　<?= e($certificate['recipient_name']) ?></h2>
            <p class="muted-text">
                <?= e(\App\Domain\Certificates\CertificateTemplates::get($certificate['template_key'])['label']) ?>
                ／開立日期：<?= e(roc_date($certificate['issued_on'])) ?>
                ／建立人：<?= e($certificate['created_by_name'] ?? '-') ?>
            </p>
        </div>
        <div class="actions">
            <a class="btn" href="/certificates">返回列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage)): ?>
                <a class="btn" href="/certificates/<?= e((string) $certificate['id']) ?>/edit">編輯</a>
                <a class="btn" href="/certificates/<?= e((string) $certificate['id']) ?>/duplicate">複製</a>
                <form method="post" action="/certificates/<?= e((string) $certificate['id']) ?>/delete" onsubmit="return confirm('確定要刪除此文件？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<article class="certificate-sheet">
    <span class="cert-corner cert-corner--tl" aria-hidden="true"></span>
    <span class="cert-corner cert-corner--tr" aria-hidden="true"></span>
    <span class="cert-corner cert-corner--bl" aria-hidden="true"></span>
    <span class="cert-corner cert-corner--br" aria-hidden="true"></span>

    <div class="cert-org"><?= e($profile['foundation_name'] ?? foundation_name()) ?></div>
    <div class="cert-title"><?= e($certificate['title']) ?></div>
    <div class="cert-divider">❖</div>
    <div class="cert-body"><?= nl2br(e($certificate['body_text'])) ?></div>
    <div class="cert-signoff-row">
        <div class="cert-signoff">
            <?= e($profile['foundation_name'] ?? foundation_name()) ?><br>
            <?= e($certificate['signer_title']) ?>　<strong><?= e($certificate['signer_name']) ?></strong>
        </div>
        <div class="cert-stamp" aria-hidden="true">蓋章處</div>
    </div>
    <div class="cert-date"><?= e(roc_date($certificate['issued_on'])) ?></div>
</article>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
