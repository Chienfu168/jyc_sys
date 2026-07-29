<?php
$active = 'accounting';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>啟用科目</span>
        <strong><?= e(number_format((int) $summary['account_count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>本月傳票</span>
        <strong><?= e(number_format((int) $summary['voucher_count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>已過帳</span>
        <strong><?= e(number_format((int) $summary['posted_count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>本月借貸差額</span>
        <strong><?= e(accounting_money($summary['debit_total'] - $summary['credit_total'])) ?></strong>
    </div>
</section>

<section class="module-grid accounting-actions">
    <a class="module-card" href="/accounting/vouchers">
        <span>會計作業</span>
        <strong>會計傳票</strong>
        <p>建立借貸分錄、草稿保存、過帳、作廢與列印。</p>
    </a>
    <a class="module-card" href="/accounting/accounts">
        <span>基本設定</span>
        <strong>會計科目</strong>
        <p>維護非營利組織常用科目、類型、上層科目與借貸方向。</p>
    </a>
    <a class="module-card" href="/income-expenses">
        <span>資料來源</span>
        <strong>收支紀錄</strong>
        <p>後續將可由收支紀錄自動拋轉會計傳票。</p>
    </a>
    <a class="module-card" href="/bank-accounts">
        <span>資料來源</span>
        <strong>銀行帳戶</strong>
        <p>後續將可由銀行交易與零用金撥補建立傳票。</p>
    </a>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>會計系統建置進度</h2>
            <p class="muted-text">目前先建立總帳核心資料，下一階段再把零用金、銀行、收支紀錄串成自動傳票。</p>
        </div>
    </div>
    <div class="feature-grid">
        <div class="feature-card">
            <span>已完成</span>
            <strong>科目表</strong>
            <p>可新增、編輯、停用科目。</p>
        </div>
        <div class="feature-card">
            <span>已完成</span>
            <strong>傳票分錄</strong>
            <p>借貸平衡後可過帳。</p>
        </div>
        <div class="feature-card">
            <span>待串接</span>
            <strong>自動拋轉</strong>
            <p>銀行、零用金、收支紀錄自動產生傳票。</p>
        </div>
    </div>
</section>
<?php
function accounting_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
