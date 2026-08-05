<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>登記狀態</span>
        <?php $registrationStatus = old('registration_status', $asset['registration_status'] ?? 'not_registered'); ?>
        <select name="registration_status" required>
            <option value="court_registered" <?= $registrationStatus === 'court_registered' ? 'selected' : '' ?>>經法院登記</option>
            <option value="not_registered" <?= $registrationStatus === 'not_registered' ? 'selected' : '' ?>>未經法院登記</option>
        </select>
    </label>
    <label>
        <span>財產種類</span>
        <?php $kind = old('asset_kind', $asset['asset_kind'] ?? 'movable'); ?>
        <select name="asset_kind" required>
            <option value="movable" <?= $kind === 'movable' ? 'selected' : '' ?>>動產</option>
            <option value="real_estate" <?= $kind === 'real_estate' ? 'selected' : '' ?>>不動產</option>
            <option value="other" <?= $kind === 'other' ? 'selected' : '' ?>>其他</option>
        </select>
    </label>
    <label class="span-2">
        <span>財產名稱</span>
        <input type="text" name="asset_name" list="asset-name-options" value="<?= e((string) old('asset_name', $asset['asset_name'] ?? '')) ?>" required>
        <datalist id="asset-name-options">
            <option value="現金"></option>
            <option value="銀行存款"></option>
            <option value="銀行定期存款"></option>
            <option value="辦公設備"></option>
            <option value="電腦設備"></option>
        </datalist>
    </label>
    <label>
        <span>單位</span>
        <input type="text" name="unit" value="<?= e((string) old('unit', $asset['unit'] ?? '式')) ?>">
    </label>
    <label>
        <span>數量</span>
        <input type="number" step="0.01" min="0" name="quantity" value="<?= e((string) old('quantity', $asset['quantity'] ?? '1')) ?>">
    </label>
    <label>
        <span>金額</span>
        <input type="number" step="1" min="0" name="amount" value="<?= e((string) old('amount', $asset['amount'] ?? '')) ?>">
    </label>
    <label>
        <span>取得日期</span>
        <input type="date" name="acquired_on" value="<?= e((string) old('acquired_on', $asset['acquired_on'] ?? '')) ?>">
    </label>
    <label>
        <span>取得來源</span>
        <input type="text" name="acquisition_source" list="asset-source-options" value="<?= e((string) old('acquisition_source', $asset['acquisition_source'] ?? '')) ?>">
        <datalist id="asset-source-options">
            <option value="設立捐助人捐助"></option>
            <option value="設立基金孳息及捐贈收入"></option>
            <option value="年度預算購置"></option>
            <option value="專案補助購置"></option>
        </datalist>
    </label>
    <label>
        <span>存放地點</span>
        <input type="text" name="storage_location" value="<?= e((string) old('storage_location', $asset['storage_location'] ?? '')) ?>">
    </label>
    <label>
        <span>證明文件</span>
        <input type="text" name="proof_document" value="<?= e((string) old('proof_document', $asset['proof_document'] ?? '')) ?>">
    </label>
    <label>
        <span>財產編號</span>
        <input type="text" name="asset_no" value="<?= e((string) old('asset_no', $asset['asset_no'] ?? '')) ?>">
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $asset['status'] ?? 'active'); ?>
        <select name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>使用中</option>
            <option value="disposed" <?= $status === 'disposed' ? 'selected' : '' ?>>已除帳</option>
        </select>
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $asset['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/foundation-assets">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
