<?php

namespace App\Modules\BankSlips\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Lookups;
use App\Core\Validator;
use App\Domain\Bank\BankSlipSettings;
use App\Domain\Bank\TwCurrency;
use App\Support\BankSlipCatalog;
use PDO;

/**
 * 銀行匯款單(取款憑條):基金會作為匯款人製作可交付銀行的匯款單並列印。
 * 目前支援彰化銀行;設計以 bank_code + 目錄 + 專屬版面保留擴充其他銀行的彈性。
 */
final class BankSlipController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('bank_slips.view');

        $stmt = Database::pdo()->query(
            'SELECT bank_slips.*, bank_accounts.bank_name AS withdrawal_bank_name, bank_accounts.account_no AS withdrawal_account_no,
                    users.name AS created_by_name
             FROM bank_slips
             LEFT JOIN bank_accounts ON bank_accounts.id = bank_slips.withdrawal_bank_account_id
             LEFT JOIN users ON users.id = bank_slips.created_by
             ORDER BY bank_slips.slip_date DESC, bank_slips.id DESC'
        );

        $this->render('bank-slips.index', [
            'title' => '匯款單(取款條)',
            'section' => '財務會計',
            'active' => 'bank-slips',
            'slips' => $stmt->fetchAll(),
            'banks' => BankSlipCatalog::all(),
            'canManage' => \App\Core\Permission::can('bank_slips.manage'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('bank_slips.manage');

        $bankCode = BankSlipCatalog::has((string) ($_GET['bank'] ?? '')) ? (string) $_GET['bank'] : BankSlipCatalog::defaultCode();
        if (!BankSlipSettings::isUsable($bankCode)) {
            flash('error', BankSlipCatalog::name($bankCode) . ' 尚未啟用或設定,請先於「匯款單設定」完成設定。');
            redirect('/bank-slips/settings');
        }

        $settings = BankSlipSettings::forBank($bankCode);
        $profile = foundation_profile();

        $this->render('bank-slips.create', [
            'title' => '製作匯款單',
            'section' => '財務會計',
            'active' => 'bank-slips',
            'bankCode' => $bankCode,
            'bank' => BankSlipCatalog::get($bankCode),
            'settings' => $settings,
            'bankAccounts' => Lookups::activeBankAccounts(),
            'slip' => [
                'slip_date' => date('Y-m-d'),
                'remittance_type' => $settings['default_remittance_type'] ?: '一般跨行匯款(11)',
                'withdrawal_bank_account_id' => $settings['withdrawal_bank_account_id'],
                'remitter_name' => $settings['remitter_name'] ?: ($profile['foundation_name'] ?? ''),
                'remitter_id_no' => $settings['remitter_id_no'] ?: ($profile['tax_id'] ?? ''),
                'remitter_phone' => $settings['remitter_phone'] ?: ($profile['phone'] ?? ''),
                'agent_name' => '',
                'payee_name' => '',
                'payee_account' => '',
                'paying_bank_name' => '',
                'amount' => '',
                'message' => '',
                'sms_mobile' => '',
                'notes' => '',
            ],
            'action' => '/bank-slips',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('bank_slips.manage');

        $bankCode = BankSlipCatalog::has((string) ($_POST['bank_code'] ?? '')) ? (string) $_POST['bank_code'] : '';
        if ($bankCode === '' || !BankSlipSettings::isUsable($bankCode)) {
            $this->backWithInput('/bank-slips/create', $_POST, '銀行未啟用或不支援。');
        }

        if ($error = Validator::required($_POST, [
            'slip_date' => '匯款日期',
            'payee_name' => '收款人戶名',
            'payee_account' => '收款人帳號',
            'amount' => '匯款金額',
        ])) {
            $this->backWithInput('/bank-slips/create?bank=' . $bankCode, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['slip_date'])) {
            $this->backWithInput('/bank-slips/create?bank=' . $bankCode, $_POST, '匯款日期格式不正確。');
        }

        $amount = (int) round((float) ($_POST['amount'] ?? 0));
        if ($amount <= 0) {
            $this->backWithInput('/bank-slips/create?bank=' . $bankCode, $_POST, '匯款金額需大於 0。');
        }

        $withdrawalId = (int) ($_POST['withdrawal_bank_account_id'] ?? 0);

        Database::pdo()->prepare(
            'INSERT INTO bank_slips
             (bank_code, slip_date, remittance_type, withdrawal_bank_account_id, remitter_name, remitter_id_no, remitter_phone, agent_name,
              payee_name, payee_account, paying_bank_name, amount, message, sms_mobile, notes, created_by, created_at, updated_at)
             VALUES
             (:bank_code, :slip_date, :remittance_type, :withdrawal_bank_account_id, :remitter_name, :remitter_id_no, :remitter_phone, :agent_name,
              :payee_name, :payee_account, :paying_bank_name, :amount, :message, :sms_mobile, :notes, :created_by, :created_at, :updated_at)'
        )->execute([
            'bank_code' => $bankCode,
            'slip_date' => (string) $_POST['slip_date'],
            'remittance_type' => trim((string) ($_POST['remittance_type'] ?? '')),
            'withdrawal_bank_account_id' => $withdrawalId > 0 ? $withdrawalId : null,
            'remitter_name' => trim((string) ($_POST['remitter_name'] ?? '')),
            'remitter_id_no' => trim((string) ($_POST['remitter_id_no'] ?? '')),
            'remitter_phone' => trim((string) ($_POST['remitter_phone'] ?? '')),
            'agent_name' => trim((string) ($_POST['agent_name'] ?? '')),
            'payee_name' => trim((string) $_POST['payee_name']),
            'payee_account' => trim((string) $_POST['payee_account']),
            'paying_bank_name' => trim((string) ($_POST['paying_bank_name'] ?? '')),
            'amount' => $amount,
            'message' => mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 30),
            'sms_mobile' => trim((string) ($_POST['sms_mobile'] ?? '')),
            'notes' => mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 255),
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'bank_slips', 'bank_slips', $id);
        flash('success', '匯款單已建立,可直接列印交付銀行。');
        redirect('/bank-slips/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('bank_slips.view');

        $slip = $this->findSlip((int) $id);
        $bank = BankSlipCatalog::get((string) $slip['bank_code']) ?? BankSlipCatalog::get(BankSlipCatalog::defaultCode());

        $this->render($bank['layout'], [
            'title' => '匯款單 - ' . BankSlipCatalog::name((string) $slip['bank_code']),
            'section' => '財務會計',
            'active' => 'bank-slips',
            'slip' => $slip,
            'amountUppercase' => TwCurrency::uppercase((int) $slip['amount']),
            'amountColumns' => TwCurrency::digitColumns((int) $slip['amount']),
            'columnLabels' => TwCurrency::COLUMN_LABELS,
            'printable' => true,
        ]);
    }

    public function destroy(string $id): void
    {
        $slip = $this->findSlip((int) $id);
        $this->requireManageOrOwner('bank_slips.manage', $slip['created_by'] ?? null);

        Database::pdo()->prepare('DELETE FROM bank_slips WHERE id = :id')->execute(['id' => (int) $id]);
        AuditLog::write('delete', 'bank_slips', 'bank_slips', (int) $id, ['payee' => $slip['payee_name'] ?? null]);
        flash('success', '匯款單已刪除。');
        redirect('/bank-slips');
    }

    public function settings(): void
    {
        $this->requirePermission('bank_slips.manage');

        $rows = [];
        foreach (BankSlipCatalog::all() as $code => $bank) {
            $rows[$code] = ['bank' => $bank, 'settings' => BankSlipSettings::forBank($code)];
        }

        $this->render('bank-slips.settings', [
            'title' => '匯款單設定',
            'section' => '財務會計',
            'active' => 'bank-slips',
            'rows' => $rows,
            'bankAccounts' => Lookups::activeBankAccounts(),
            'profile' => foundation_profile(),
        ]);
    }

    public function saveSettings(): void
    {
        $this->requirePermission('bank_slips.manage');

        $bankCode = BankSlipCatalog::has((string) ($_POST['bank_code'] ?? '')) ? (string) $_POST['bank_code'] : '';
        if ($bankCode === '') {
            $this->backWithInput('/bank-slips/settings', [], '不支援的銀行。');
        }

        $withdrawalId = (int) ($_POST['withdrawal_bank_account_id'] ?? 0);
        $ok = BankSlipSettings::save($bankCode, [
            'enabled' => ($_POST['enabled'] ?? '') === '1',
            'withdrawal_bank_account_id' => $withdrawalId > 0 ? $withdrawalId : null,
            'remitter_name' => trim((string) ($_POST['remitter_name'] ?? '')),
            'remitter_id_no' => trim((string) ($_POST['remitter_id_no'] ?? '')),
            'remitter_phone' => trim((string) ($_POST['remitter_phone'] ?? '')),
            'default_remittance_type' => trim((string) ($_POST['default_remittance_type'] ?? '')) ?: '一般跨行匯款(11)',
        ]);

        if (!$ok) {
            $this->backWithInput('/bank-slips/settings', [], '設定儲存失敗,請確認 storage 目錄是否可寫入。');
        }

        AuditLog::write('update_settings', 'bank_slips', 'bank_slips', null, ['bank_code' => $bankCode]);
        flash('success', BankSlipCatalog::name($bankCode) . ' 匯款單設定已更新。');
        redirect('/bank-slips/settings');
    }

    private function findSlip(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT bank_slips.*, bank_accounts.bank_name AS withdrawal_bank_name, bank_accounts.branch_name AS withdrawal_branch_name,
                    bank_accounts.account_name AS withdrawal_account_name, bank_accounts.account_no AS withdrawal_account_no
             FROM bank_slips
             LEFT JOIN bank_accounts ON bank_accounts.id = bank_slips.withdrawal_bank_account_id
             WHERE bank_slips.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $slip = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$slip) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到匯款單']);
            exit;
        }

        return $slip;
    }
}
