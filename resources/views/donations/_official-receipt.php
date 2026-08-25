<?php
/**
 * 官方樣式捐贈收據(單張),供單筆與批次列印共用。
 * 需在外層提供 $donation(捐款資料列)與 $profile(基金會基本資料)。
 * 版面參考基金會實體收據,並於下方保留大小章用印區。
 */
$rcProfile = $profile ?? foundation_profile();
$rcName = $rcProfile['foundation_name'] ?? foundation_name();
$rcDonor = $donation['receipt_title'] ?? '';
if ($rcDonor === '') {
    $rcDonor = $donation['donor_name'] ?? '';
}
$rcTaxId = trim((string) ($donation['tax_id'] ?? ''));
$rcAmount = (int) round((float) ($donation['amount'] ?? 0));
$rcGrid = \App\Domain\Donations\AmountInWords::grid($rcAmount);
$rcChairman = trim((string) ($rcProfile['representative'] ?? ''));
$rcApprovalDoc = trim((string) ($rcProfile['approval_doc_no'] ?? ''));
$rcProject = trim((string) ($donation['project_name'] ?? ''));
$rcInKind = ($donation['donation_kind'] ?? 'cash') === 'in_kind';
$rcInKindItem = trim((string) ($donation['in_kind_item'] ?? '')) ?: ($rcProject !== '' && $rcProject !== '一般捐款' ? $rcProject : '');
?>
<article class="official-receipt">
    <header class="rc-head">
        <h2 class="rc-org"><?= e($rcName) ?></h2>
        <div class="rc-title">收　據</div>
    </header>

    <div class="rc-datno">
        <span class="rc-date"><?= e(roc_date($donation['donated_at'] ?? null)) ?></span>
        <?php $rcNo = trim((string) ($donation['receipt_no'] ?? '')) ?: trim((string) ($donation['donation_no'] ?? '')); ?>
        <span class="rc-no">No.<?= e($rcNo) ?></span>
    </div>

    <p class="rc-recital">
        茲收捐贈人 <span class="rc-fill"><?= e($rcDonor) ?></span>
        ；統編：<span class="rc-fill rc-fill-sm"><?= e($rcTaxId !== '' ? $rcTaxId : '—') ?></span>。捐贈如下；
    </p>

    <div class="rc-body">
        <div class="rc-row">
            <span class="rc-check"><?= $rcInKind ? '□' : '■' ?></span>樂捐款：
            <?php if (!$rcInKind): ?>
                <span class="rc-amount-cn">新台幣
                    <?php foreach ($rcGrid as $cell): ?><span class="rc-cell"><span class="rc-d"><?= e($cell['digit']) ?></span><span class="rc-u"><?= e($cell['unit']) ?></span></span><?php endforeach; ?>整
                </span>
            <?php endif; ?>
        </div>
        <?php if (!$rcInKind): ?>
            <div class="rc-row rc-nt">NT：<?= e(number_format($rcAmount)) ?> 元整</div>
        <?php endif; ?>
        <div class="rc-row">
            <span class="rc-check"><?= $rcInKind ? '■' : '□' ?></span>樂捐實物：
            <span class="rc-fill rc-fill-long"><?= e($rcInKind ? $rcInKindItem : '') ?></span>
        </div>
        <?php if ($rcInKind && $rcAmount > 0): ?>
            <div class="rc-row rc-nt">估計價值 NT：<?= e(number_format($rcAmount)) ?> 元整</div>
        <?php endif; ?>
    </div>

    <p class="rc-thanks">上列經如數收訖，承蒙台端關懷贊助，特此敬致謝忱！</p>

    <div class="rc-stamps">
        <div class="rc-stamp-org">
            <span class="rc-stamp-label">基金會章</span>
            <span class="rc-stamp-box" aria-hidden="true"></span>
        </div>
        <div class="rc-stamp-people">
            <div class="rc-stamp-line">會計：<span class="rc-stamp-space"></span></div>
            <div class="rc-stamp-line">經手人：<span class="rc-stamp-space"></span></div>
            <div class="rc-stamp-line">董事長：<span class="rc-chairman"><?= e($rcChairman) ?></span><span class="rc-stamp-space rc-stamp-seal" aria-hidden="true"></span></div>
        </div>
    </div>

    <footer class="rc-footer">
        <div>會址：<?= e($rcProfile['address'] ?? '') ?>　電話：<?= e($rcProfile['phone'] ?? '') ?></div>
        <?php if (!empty($rcProfile['mailing_address']) && $rcProfile['mailing_address'] !== ($rcProfile['address'] ?? '')): ?>
            <div>通訊地址：<?= e($rcProfile['mailing_address']) ?></div>
        <?php endif; ?>
        <div>統一編號：<?= e($rcProfile['tax_id'] ?? '') ?><?= $rcApprovalDoc !== '' ? '　' . e($rcApprovalDoc) : '' ?></div>
        <?php if (!empty($rcProfile['website']) || !empty($rcProfile['email'])): ?>
            <div>
                <?php if (!empty($rcProfile['website'])): ?>網址：<?= e($rcProfile['website']) ?><?php endif; ?>
                <?php if (!empty($rcProfile['website']) && !empty($rcProfile['email'])): ?>　<?php endif; ?>
                <?php if (!empty($rcProfile['email'])): ?>Email：<?= e($rcProfile['email']) ?><?php endif; ?>
            </div>
        <?php endif; ?>
    </footer>
</article>
