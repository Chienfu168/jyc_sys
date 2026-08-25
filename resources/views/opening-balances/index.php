<?php
$active = 'opening-balances';
ob_start();
?>
<section class="panel no-print">
    <div class="panel-header">
        <div>
            <h2>期初餘額（年度結轉）</h2>
            <p class="muted-text">以年度為單位設定各帳本的期初餘額，讓系統可承接前年度結餘，不需遷移全部歷史資料。</p>
        </div>
    </div>
    <form class="form grid-form" method="get" action="/opening-balances">
        <label>
            <span>民國年度</span>
            <input type="number" name="year" min="1" max="2100" value="<?= e((string) roc_year($year)) ?>">
        </label>
        <div class="form-actions">
            <button class="btn primary" type="submit">查詢</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e(roc_year_label($year)) ?> 帳本期初餘額</h2>
            <p class="muted-text">期末餘額 = 期初餘額 ＋ 本年度收入 − 本年度支出，並自動結轉為下一年度期初。</p>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>帳本</th>
                <th class="amount">目前期初餘額</th>
                <th class="amount">前一年度期末結餘</th>
                <?php if ($canManage): ?><th>設定</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ledgers as $ledger): ?>
                <tr>
                    <td><?= e($ledger['label']) ?></td>
                    <td class="amount">
                        <?php if ($ledger['has_opening']): ?>
                            <?= e(number_format((float) $ledger['opening'], 0)) ?>
                        <?php else: ?>
                            <span class="muted-text">未設定</span>
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?= e(number_format((float) $ledger['previous_closing'], 0)) ?></td>
                    <?php if ($canManage): ?>
                        <td>
                            <form class="inline-opening-form" method="post" action="/opening-balances">
                                <?= csrf_field() ?>
                                <input type="hidden" name="module" value="<?= e($ledger['module']) ?>">
                                <input type="hidden" name="year" value="<?= e((string) roc_year($year)) ?>">
                                <input type="number" step="1" name="opening_balance" value="<?= e($ledger['has_opening'] ? (string) (int) $ledger['opening'] : '') ?>" placeholder="0" style="max-width:140px">
                                <button class="btn small" type="button" data-carry="<?= e((string) (int) $ledger['previous_closing']) ?>">帶入前年度結餘</button>
                                <button class="btn small primary" type="submit">儲存</button>
                            </form>
                            <?php if ($ledger['has_opening'] && \App\Core\Permission::can('opening_balances.delete')): ?>
                                <form class="inline-opening-form" method="post" action="/opening-balances/delete" onsubmit="return confirm('確定要「刪除」此帳本本年度的期初餘額設定？刪除後此年度將回到「未設定」狀態。');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="module" value="<?= e($ledger['module']) ?>">
                                    <input type="hidden" name="year" value="<?= e((string) roc_year($year)) ?>">
                                    <button class="btn small danger" type="submit">刪除</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>銀行帳戶（連續結轉）</h2>
            <p class="muted-text">銀行帳戶以「開戶期初餘額 ＋ 歷來交易」連續結轉，餘額本身即已跨年度累積，無需另設每年度期初。下列為目前狀態參考。</p>
        </div>
        <a class="btn no-print" href="/bank-accounts">前往銀行帳戶</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>銀行 / 帳號</th>
                <th class="amount">開戶期初餘額</th>
                <th class="amount">目前結餘</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$bankAccounts): ?>
                <tr><td colspan="3" class="muted-text">尚未建立銀行帳戶。</td></tr>
            <?php endif; ?>
            <?php foreach ($bankAccounts as $account): ?>
                <tr>
                    <td><?= e($account['bank_name'] . ' / ' . $account['account_no']) ?></td>
                    <td class="amount"><?= e(number_format((float) $account['opening_balance'], 0)) ?></td>
                    <td class="amount"><?= e(number_format((float) $account['current_balance'], 0)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
document.querySelectorAll('[data-carry]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.closest('form')?.querySelector('input[name="opening_balance"]');
        if (input) {
            input.value = button.dataset.carry || '0';
        }
    });
});
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
