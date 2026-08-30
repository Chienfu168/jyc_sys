<?php
$active = 'petty-cash';
$items = $items ?? [];
$expenseItems = array_values(array_filter($items, static fn (array $i): bool => $i['item_type'] === 'expense'));
$incomeItems = array_values(array_filter($items, static fn (array $i): bool => $i['item_type'] === 'income'));
ob_start();
?>
<section class="panel narrow pcq">
    <div class="panel-header">
        <div>
            <h2>零用金快速記帳</h2>
            <p class="muted-text">手機即時記一筆並拍照留存憑證,存為草稿;之後可在電腦上補齊項目與送審。</p>
        </div>
        <a class="btn" href="/petty-cash">完整清單</a>
    </div>

    <form method="post" action="/petty-cash/quick" enctype="multipart/form-data" class="form pcq-form" id="pcqForm">
        <?= csrf_field() ?>

        <div class="pcq-type" role="group" aria-label="類型">
            <label class="pcq-type__btn">
                <input type="radio" name="item_type" value="expense" <?= old('item_type', 'expense') !== 'income' ? 'checked' : '' ?>>
                <span>支出</span>
            </label>
            <label class="pcq-type__btn">
                <input type="radio" name="item_type" value="income" <?= old('item_type') === 'income' ? 'checked' : '' ?>>
                <span>收入</span>
            </label>
        </div>

        <label class="pcq-amount">
            <span>金額</span>
            <input type="number" name="amount" inputmode="decimal" step="0.01" min="0" placeholder="0" value="<?= e((string) old('amount', '')) ?>" required autofocus>
        </label>

        <div class="pcq-field">
            <span class="pcq-label">項目</span>
            <div class="pcq-chips" id="pcqChips" data-target="pcqItemName">
                <?php foreach ($expenseItems as $it): ?>
                    <button type="button" class="pcq-chip" data-type="expense" data-name="<?= e($it['name']) ?>"<?= $it['default_amount'] !== null ? ' data-amount="' . e((string) (int) $it['default_amount']) . '"' : '' ?>><?= e($it['name']) ?></button>
                <?php endforeach; ?>
                <?php foreach ($incomeItems as $it): ?>
                    <button type="button" class="pcq-chip" data-type="income" data-name="<?= e($it['name']) ?>"<?= $it['default_amount'] !== null ? ' data-amount="' . e((string) (int) $it['default_amount']) . '"' : '' ?> hidden><?= e($it['name']) ?></button>
                <?php endforeach; ?>
            </div>
            <input type="text" name="item_name" id="pcqItemName" maxlength="160" placeholder="點上方常用項目,或直接輸入項目名稱" value="<?= e((string) old('item_name', '')) ?>">
        </div>

        <label class="pcq-field">
            <span class="pcq-label">憑證照片(可多張,會自動壓縮)</span>
            <input type="file" name="receipts[]" id="pcqReceipts" accept="image/*,application/pdf" capture="environment" multiple>
            <span class="field-hint">可直接拍照或從相簿選取;照片會在上傳前自動壓縮以節省流量與空間。PDF 原樣保留。</span>
        </label>
        <div class="pcq-previews" id="pcqPreviews" hidden></div>

        <details class="pcq-more">
            <summary>更多欄位(日期／對象／備註)</summary>
            <label class="pcq-field">
                <span class="pcq-label">日期</span>
                <input type="date" name="occurred_on" value="<?= e((string) old('occurred_on', $today)) ?>" required>
            </label>
            <label class="pcq-field">
                <span class="pcq-label">支付對象／來源(選填)</span>
                <input type="text" name="payment_to" maxlength="160" value="<?= e((string) old('payment_to', '')) ?>">
            </label>
            <label class="pcq-field">
                <span class="pcq-label">備註(選填)</span>
                <textarea name="notes" rows="2"><?= e((string) old('notes', '')) ?></textarea>
            </label>
        </details>

        <div class="form-actions">
            <button class="btn primary pcq-submit" type="submit" id="pcqSubmit">記一筆</button>
        </div>
    </form>
</section>

<?php if (!empty($recent)): ?>
<section class="panel narrow">
    <div class="panel-header"><h2>最近記錄</h2></div>
    <table class="data-table">
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><?= e(roc_date($r['occurred_on'])) ?></td>
                <td><?= e($r['item_name']) ?></td>
                <td style="text-align:right;white-space:nowrap"><?= $r['item_type'] === 'income' ? '+' : '-' ?><?= e(number_format((float) $r['amount'])) ?></td>
                <td style="text-align:right"><a class="btn" href="/petty-cash/<?= e((string) $r['id']) ?>">明細</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>

<script>
(function () {
    // 類型切換:顯示對應的常用項目 chip。
    var chips = document.getElementById('pcqChips');
    var itemNameInput = document.getElementById('pcqItemName');
    var amountInput = document.querySelector('.pcq-amount input');

    function currentType() {
        var el = document.querySelector('input[name="item_type"]:checked');
        return el ? el.value : 'expense';
    }
    function refreshChips() {
        var t = currentType();
        Array.prototype.forEach.call(chips.querySelectorAll('.pcq-chip'), function (c) {
            c.hidden = c.getAttribute('data-type') !== t;
        });
    }
    Array.prototype.forEach.call(document.querySelectorAll('input[name="item_type"]'), function (r) {
        r.addEventListener('change', refreshChips);
    });
    refreshChips();

    chips.addEventListener('click', function (e) {
        var chip = e.target.closest('.pcq-chip');
        if (!chip) return;
        itemNameInput.value = chip.getAttribute('data-name') || '';
        Array.prototype.forEach.call(chips.querySelectorAll('.pcq-chip'), function (c) { c.classList.remove('is-active'); });
        chip.classList.add('is-active');
        if (amountInput && !amountInput.value && chip.getAttribute('data-amount')) {
            amountInput.value = chip.getAttribute('data-amount');
        }
    });

    // 上傳前於瀏覽器端壓縮照片(長邊 1600、JPEG 0.7),降低流量。
    var fileInput = document.getElementById('pcqReceipts');
    var previews = document.getElementById('pcqPreviews');
    var compressed = null; // DataTransfer 保存壓縮後檔案

    function compressImage(file) {
        return new Promise(function (resolve) {
            if (!/^image\//.test(file.type) || typeof createImageBitmap === 'undefined') {
                resolve(file); return;
            }
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                URL.revokeObjectURL(url);
                var max = 1600;
                var scale = Math.min(1, max / Math.max(img.width, img.height));
                var w = Math.max(1, Math.round(img.width * scale));
                var h = Math.max(1, Math.round(img.height * scale));
                var canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                var ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
                ctx.drawImage(img, 0, 0, w, h);
                canvas.toBlob(function (blob) {
                    if (!blob) { resolve(file); return; }
                    var name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg' }));
                }, 'image/jpeg', 0.7);
            };
            img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    }

    fileInput.addEventListener('change', function () {
        previews.innerHTML = '';
        var files = Array.prototype.slice.call(fileInput.files || []);
        if (!files.length) { previews.hidden = true; compressed = null; return; }
        previews.hidden = false;
        Promise.all(files.map(compressImage)).then(function (out) {
            try {
                var dt = new DataTransfer();
                out.forEach(function (f) { dt.items.add(f); });
                fileInput.files = dt.files;
                compressed = dt;
            } catch (e) { /* 部分瀏覽器不支援改寫 files,退回原檔由伺服器壓縮 */ }
            out.forEach(function (f) {
                if (!/^image\//.test(f.type)) {
                    var tag = document.createElement('span');
                    tag.className = 'pcq-preview pcq-preview--file';
                    tag.textContent = f.name;
                    previews.appendChild(tag);
                    return;
                }
                var u = URL.createObjectURL(f);
                var im = document.createElement('img');
                im.className = 'pcq-preview';
                im.src = u;
                im.onload = function () { URL.revokeObjectURL(u); };
                previews.appendChild(im);
            });
        });
    });

    // 避免重複送出。
    var form = document.getElementById('pcqForm');
    var submit = document.getElementById('pcqSubmit');
    form.addEventListener('submit', function () {
        submit.disabled = true;
        submit.textContent = '儲存中…';
    });
})();
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
