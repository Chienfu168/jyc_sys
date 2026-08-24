<?php
$active = 'official-letters';
$canManage = \App\Core\Permission::can('official_letters.manage');
$foundationName = $profile['foundation_name'] ?? foundation_name();
ob_start();
?>
<section class="panel no-print">
    <div class="panel-header">
        <div>
            <p class="eyebrow">陳報公文</p>
            <h2><?= e(mb_strimwidth((string) $letter['subject'], 0, 40, '…')) ?></h2>
            <p class="muted-text"><?= e(roc_year_label($letter['fiscal_year'])) ?> / <?= $letter['status'] === 'issued' ? '已發文' : '草稿' ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/official-letters">返回列表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/official-letters/<?= e((string) $letter['id']) ?>/edit">編輯</a>
                <?php if ($letter['status'] === 'draft'): ?>
                    <form method="post" action="/official-letters/<?= e((string) $letter['id']) ?>/issue">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">標記已發文</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>
</section>

<article class="official-letter">
    <div class="ol-org-seal" aria-hidden="true"><?= e($foundationName . '印') ?></div>

    <div class="ol-head">
        <p class="ol-org"><?= e($foundationName) ?>函</p>
        <div class="ol-address">
            <p>會址：<?= e($profile['address'] ?: '-') ?></p>
            <?php if (!empty($profile['mailing_address']) && $profile['mailing_address'] !== $profile['address']): ?>
                <p>聯絡地址：<?= e($profile['mailing_address']) ?></p>
            <?php endif; ?>
            <p>聯絡電話：<?= e($profile['phone'] ?: '-') ?></p>
        </div>
    </div>

    <table class="ol-meta">
        <tbody>
        <tr><th>受文者</th><td colspan="3"><?= e($letter['recipient']) ?></td></tr>
        <tr>
            <th>速別</th><td><?= e($letter['urgency']) ?></td>
            <th>密等及解密條件</th><td><?= e($letter['confidentiality'] ?: '') ?></td>
        </tr>
        <tr>
            <th>發文日期</th><td><?= e(roc_date($letter['letter_date'])) ?></td>
            <th>發文字號</th><td><?= e($letter['letter_number'] ?: '') ?></td>
        </tr>
        <tr><th>附件</th><td colspan="3"><?= e($letter['attachment_note'] ?: '') ?></td></tr>
        </tbody>
    </table>

    <p class="ol-subject"><span class="ol-label">主旨：</span><?= nl2br(e($letter['subject'])) ?></p>

    <div class="ol-body">
        <p class="ol-label">說明：</p>
        <ol class="ol-basis">
            <?php foreach ($basisLines as $index => $line): ?>
                <li><?= e(\App\Domain\OfficialLetters\LetterFormat::ordinal($index + 1)) ?>、<?= nl2br(e($line)) ?></li>
            <?php endforeach; ?>
            <?php if ($attachmentItems): ?>
                <li>
                    <?= e(\App\Domain\OfficialLetters\LetterFormat::ordinal(count($basisLines) + 1)) ?>、<?= e($letter['attachment_intro'] ?: '檢附左列文件各 1 份：') ?>
                    <ol class="ol-attachments">
                        <?php foreach ($attachmentItems as $subIndex => $item): ?>
                            <li>（<?= e(\App\Domain\OfficialLetters\LetterFormat::ordinal($subIndex + 1)) ?>）<?= nl2br(e($item)) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </li>
            <?php endif; ?>
        </ol>
    </div>

    <div class="ol-signature">
        <span class="ol-signer-name"><?= e($letter['signer_name'] ?: '') ?></span>
        <span class="ol-signer-title"><?= e($letter['signer_title']) ?>（簽名或蓋章）</span>
    </div>
</article>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
