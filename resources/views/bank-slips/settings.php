<?php
$active = 'bank-slips';
$rows = $rows ?? [];
$bankAccounts = $bankAccounts ?? [];
$profile = $profile ?? foundation_profile();
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>匯款單設定</h2>
            <p class="muted-text">設定各銀行匯款單的匯款人與預設取款帳戶。目前支援彰化銀行,未來可擴充其他銀行。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/bank-slips">返回匯款單</a>
        </div>
    </div>

    <?php foreach ($rows as $code => $row): ?>
        <?php $s = $row['settings']; $bank = $row['bank']; $sel = (string) ($s['withdrawal_bank_account_id'] ?? ''); ?>
        <div class="form-section" style="border:1px solid #e2e6e4;border-radius:8px;padding:16px;margin-bottom:16px">
            <h3><?= e($bank['name']) ?><?php if (!empty($bank['code'])): ?> <span class="muted-text" style="font-weight:400">(銀行代號 <?= e($bank['code']) ?>)</span><?php endif; ?></h3>

            <?php if (empty($bank['enabled'])): ?>
                <p class="muted-text">此銀行版面規劃中,尚無法製單。</p>
            <?php else: ?>
                <form class="form" method="post" action="/bank-slips/settings">
                    <?= csrf_field() ?>
                    <input type="hidden" name="bank_code" value="<?= e($code) ?>">
                    <div class="grid-form">
                        <label class="span-2">
                            <span>啟用此銀行匯款單</span>
                            <select name="enabled">
                                <option value="1" <?= !empty($s['enabled']) ? 'selected' : '' ?>>啟用</option>
                                <option value="0" <?= empty($s['enabled']) ? 'selected' : '' ?>>停用</option>
                            </select>
                        </label>
                        <label class="span-2">
                            <span>預設取款帳戶(基金會付款帳戶)</span>
                            <select name="withdrawal_bank_account_id">
                                <option value="">未指定</option>
                                <?php foreach ($bankAccounts as $account): ?>
                                    <option value="<?= e((string) $account['id']) ?>" <?= $sel === (string) $account['id'] ? 'selected' : '' ?>>
                                        <?= e($account['bank_name'] . ' ' . ($account['branch_name'] ?: '') . ' / ' . $account['account_no'] . ' / ' . $account['account_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-hint">建議選擇貴會在<?= e($bank['name']) ?>的帳戶。此帳戶會帶入匯款單的取款帳號欄。</span>
                        </label>
                        <label>
                            <span>匯款人名稱</span>
                            <input type="text" name="remitter_name" value="<?= e((string) ($s['remitter_name'] ?? '')) ?>" placeholder="<?= e($profile['foundation_name'] ?? '') ?>">
                        </label>
                        <label>
                            <span>統一編號</span>
                            <input type="text" name="remitter_id_no" value="<?= e((string) ($s['remitter_id_no'] ?? '')) ?>" placeholder="<?= e($profile['tax_id'] ?? '') ?>">
                        </label>
                        <label>
                            <span>電話</span>
                            <input type="text" name="remitter_phone" value="<?= e((string) ($s['remitter_phone'] ?? '')) ?>" placeholder="<?= e($profile['phone'] ?? '') ?>">
                        </label>
                        <label>
                            <span>預設匯款種類</span>
                            <input type="text" name="default_remittance_type" value="<?= e((string) ($s['default_remittance_type'] ?? '一般跨行匯款(11)')) ?>">
                        </label>
                    </div>
                    <p class="field-hint">匯款人欄位留空時,製單會自動帶入基金會基本資料(名稱／統編／電話)。</p>
                    <div class="form-actions">
                        <button class="btn primary" type="submit">儲存<?= e($bank['name']) ?>設定</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
