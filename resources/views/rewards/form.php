<?php
$active = 'rewards';
$reward = $reward ?? [];
$recipients = $recipients ?? [];
$isEdit = !empty($reward['id']);

// 明細列:優先採用驗證回填陣列,其次既有資料,最後一列空白。
$oldNames = old('recipient_name');
if (is_array($oldNames)) {
    $oldTitles = is_array(old('recipient_title')) ? old('recipient_title') : [];
    $oldContribs = is_array(old('recipient_contribution')) ? old('recipient_contribution') : [];
    $oldAmounts = is_array(old('recipient_amount')) ? old('recipient_amount') : [];
    $rows = [];
    foreach ($oldNames as $i => $n) {
        $rows[] = ['name' => $n, 'job_title' => $oldTitles[$i] ?? '', 'contribution' => $oldContribs[$i] ?? '', 'amount' => $oldAmounts[$i] ?? ''];
    }
} elseif ($recipients) {
    $rows = $recipients;
} else {
    $rows = [['name' => '', 'job_title' => '', 'contribution' => '', 'amount' => '']];
}
if (!$rows) {
    $rows = [['name' => '', 'job_title' => '', 'contribution' => '', 'amount' => '']];
}

$renderRow = static function (array $row): void { ?>
    <tr class="rw-row">
        <td data-label="人員"><input type="text" name="recipient_name[]" class="rw-name" maxlength="120" value="<?= e((string) ($row['name'] ?? '')) ?>"></td>
        <td data-label="職務"><input type="text" name="recipient_title[]" maxlength="120" value="<?= e((string) ($row['job_title'] ?? '')) ?>"></td>
        <td data-label="參與性質／貢獻"><textarea name="recipient_contribution[]" rows="2" placeholder="參與性質／貢獻說明"><?= e((string) ($row['contribution'] ?? '')) ?></textarea></td>
        <td data-label="建議獎勵金額"><input type="number" name="recipient_amount[]" class="rw-amount" inputmode="decimal" step="1" min="0" placeholder="0" value="<?= e((string) ($row['amount'] ?? '')) ?>" style="text-align:right"></td>
        <td class="rw-remove-cell"><button type="button" class="btn rw-remove" title="刪除此列" aria-label="刪除此列">✕</button></td>
    </tr>
<?php };

ob_start();
?>
<style>
.rw-items { width: 100%; border-collapse: collapse; margin-top: 4px; }
.rw-items th, .rw-items td { border: 1px solid #d9dfdc; padding: 6px 8px; vertical-align: top; }
.rw-items thead th { background: #f1f4f3; text-align: left; font-size: 13px; }
.rw-items th.amount, .rw-items td.amount { text-align: right; width: 130px; }
.rw-items td.rw-remove-cell { width: 46px; text-align: center; vertical-align: middle; }
.rw-items input, .rw-items textarea { width: 100%; box-sizing: border-box; }
.rw-items tfoot td { background: #f6f8f7; font-weight: 700; }
.rw-remove { padding: 2px 10px; }
.rw-add { margin-top: 8px; }
</style>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= $isEdit ? '編輯員工獎勵申請' : '新增員工獎勵申請' ?></h2>
            <p class="muted-text">依「員工專案及創新成果獎勵辦法」填寫;獎勵總額由各獎勵人員金額自動加總。</p>
        </div>
        <a class="btn" href="/rewards">返回清單</a>
    </div>

    <form method="post" action="<?= e($action) ?>" class="form">
        <?= csrf_field() ?>

        <div class="grid-form">
            <label class="span-2">
                <span>申請項目 <span style="color:#b32d2d">*</span></span>
                <input type="text" name="title" maxlength="200" required placeholder="例如：2026台灣永續行動獎獲獎專案成果獎勵" value="<?= e((string) old('title', $reward['title'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>獎勵事由</span>
                <input type="text" name="award_reason" maxlength="500" placeholder="例如：本會參與 TSAA 並榮獲 SDG 04「優質教育」銀獎" value="<?= e((string) old('award_reason', $reward['award_reason'] ?? '')) ?>">
            </label>
            <label>
                <span>申請日期 <span style="color:#b32d2d">*</span></span>
                <input type="date" name="apply_date" required value="<?= e((string) old('apply_date', $reward['apply_date'] ?? date('Y-m-d'))) ?>">
            </label>
        </div>

        <div class="form-section">
            <h3>一、申請事由</h3>
            <textarea name="reason" rows="4" placeholder="說明參獎經過、成果與獲獎效益等"><?= e((string) old('reason', $reward['reason'] ?? '')) ?></textarea>
        </div>

        <div class="form-section">
            <h3>二、獎勵人員及金額</h3>
            <p class="muted-text">可新增多筆獎勵人員,金額將自動加總。</p>
            <table class="rw-items entry-table" id="rwItems">
                <thead>
                    <tr>
                        <th style="width:18%">人員</th>
                        <th style="width:18%">職務</th>
                        <th>參與性質／貢獻</th>
                        <th class="amount">建議獎勵金額</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="rwItemsBody">
                    <?php foreach ($rows as $row) { $renderRow($row); } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right">合計</td>
                        <td class="amount"><span id="rwTotal">0</span> 元</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <button type="button" class="btn rw-add" id="rwAddItem">＋ 新增一列</button>
        </div>

        <div class="form-section">
            <h3>三、獎勵理由</h3>
            <textarea name="justification" rows="4" placeholder="說明各人員實際參與程度、工作內容及貢獻情形"><?= e((string) old('justification', $reward['justification'] ?? '')) ?></textarea>
        </div>

        <div class="form-section">
            <h3>四、經費</h3>
            <textarea name="funding_source" rows="2"><?= e((string) old('funding_source', $reward['funding_source'] ?? '')) ?></textarea>
        </div>

        <div class="form-section">
            <h3>五、擬辦</h3>
            <textarea name="proposed_action" rows="2"><?= e((string) old('proposed_action', $reward['proposed_action'] ?? '')) ?></textarea>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit" name="action" value="draft">儲存草稿</button>
            <button class="btn primary" type="submit" name="action" value="submit">送出申請</button>
        </div>
    </form>
</section>

<template id="rwRowTemplate">
    <?php $renderRow(['name' => '', 'job_title' => '', 'contribution' => '', 'amount' => '']); ?>
</template>

<script>
(function () {
    var body = document.getElementById('rwItemsBody');
    var tpl = document.getElementById('rwRowTemplate');
    var totalEl = document.getElementById('rwTotal');

    function recalc() {
        var sum = 0;
        body.querySelectorAll('.rw-amount').forEach(function (inp) {
            var v = parseFloat(inp.value);
            if (!isNaN(v)) { sum += v; }
        });
        totalEl.textContent = sum.toLocaleString('en-US');
    }

    function bindRow(row) {
        var amount = row.querySelector('.rw-amount');
        if (amount) { amount.addEventListener('input', recalc); }
        var remove = row.querySelector('.rw-remove');
        if (remove) {
            remove.addEventListener('click', function () {
                if (body.querySelectorAll('.rw-row').length <= 1) {
                    row.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
                } else {
                    row.parentNode.removeChild(row);
                }
                recalc();
            });
        }
    }

    Array.prototype.forEach.call(body.querySelectorAll('.rw-row'), bindRow);
    recalc();

    document.getElementById('rwAddItem').addEventListener('click', function () {
        var frag = tpl.content.cloneNode(true);
        var row = frag.querySelector('.rw-row');
        body.appendChild(row);
        bindRow(row);
        var nm = row.querySelector('.rw-name');
        if (nm) { nm.focus(); }
    });
})();
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
