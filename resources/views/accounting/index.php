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
        <span>傳票總數</span>
        <strong><?= e(number_format((int) $summary['voucher_count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>已過帳</span>
        <strong><?= e(number_format((int) $summary['posted_count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>借貸差額</span>
        <strong><?= e(accounting_money($summary['debit_total'] - $summary['credit_total'])) ?></strong>
    </div>
</section>

<section class="module-grid accounting-actions">
    <a class="module-card" href="/accounting/vouchers">
        <span>日常作業</span>
        <strong>會計傳票</strong>
        <p>建立收入、支出、轉帳與調整分錄，作為各項財務報表的資料來源。</p>
    </a>
    <a class="module-card" href="/accounting/accounts">
        <span>基礎設定</span>
        <strong>會計科目</strong>
        <p>維護資產、負債、淨資產、收入與費用科目，支援報表彙總。</p>
    </a>
    <a class="module-card" href="/accounting/reports/general-ledger">
        <span>帳冊</span>
        <strong>總帳</strong>
        <p>依科目彙總期間借貸發生額，檢視科目整體變動。</p>
    </a>
    <a class="module-card" href="/accounting/reports/detail-ledger">
        <span>帳冊</span>
        <strong>明細帳</strong>
        <p>依單一科目查詢期初、期間明細與期末餘額。</p>
    </a>
    <a class="module-card" href="/accounting/reports/trial-balance">
        <span>報表</span>
        <strong>試算表</strong>
        <p>依期初、借方、貸方、期末餘額檢查借貸是否平衡。</p>
    </a>
    <a class="module-card" href="/accounting/reports/income-statement">
        <span>報表</span>
        <strong>收支營運表</strong>
        <p>參考主管機關格式，彙總收益、費損與本期賸餘或短絀。</p>
    </a>
    <a class="module-card" href="/accounting/reports/balance-sheet">
        <span>報表</span>
        <strong>資產負債表</strong>
        <p>依結報日列示資產、負債、淨資產與本期賸餘。</p>
    </a>
    <a class="module-card" href="/accounting/reports/net-assets">
        <span>報表</span>
        <strong>淨值變動表</strong>
        <p>列示期初淨值、本年度賸餘、其他綜合餘絀與期末淨值。</p>
    </a>
    <a class="module-card" href="/accounting/reports/cash-flow">
        <span>報表</span>
        <strong>現金流量表</strong>
        <p>依現金、營運資產負債、利息與折舊項目產生現金流量初版。</p>
    </a>
    <a class="module-card" href="/foundation-assets">
        <span>報表</span>
        <strong>財產清冊</strong>
        <p>管理經法院登記與未經法院登記財產，支援列印財產清冊與明細表。</p>
    </a>
    <a class="module-card" href="/income-expenses">
        <span>來源資料</span>
        <strong>收支紀錄</strong>
        <p>日常收支登錄，可連動會計傳票與報表彙整。</p>
    </a>
    <a class="module-card" href="/bank-accounts">
        <span>來源資料</span>
        <strong>銀行帳戶</strong>
        <p>管理銀行帳戶、交易明細與對帳資料，支援現金及存款餘額追蹤。</p>
    </a>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>年度財務報表建構方向</h2>
            <p class="muted-text">已參考既有 Excel：銀行帳、零用金、差旅費、財產清冊、資產負債表、收支營運表、淨值變動表、現金流量表。</p>
        </div>
    </div>
    <div class="feature-grid">
        <div class="feature-card">
            <span>已建構</span>
            <strong>帳務與核心報表</strong>
            <p>傳票、科目、試算表、收支營運表、資產負債表、淨值變動表、現金流量表。</p>
        </div>
        <div class="feature-card">
            <span>已建構</span>
            <strong>來源資料</strong>
            <p>銀行帳戶、零用金、收支紀錄、差旅費、薪資與專案費用可逐步串接會計傳票。</p>
        </div>
        <div class="feature-card">
            <span>下一步</span>
            <strong>財產清冊維護</strong>
            <p>系統資料表已規劃財產種類、登記狀態、取得來源、存放地點、證明文件與財產編號。</p>
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
