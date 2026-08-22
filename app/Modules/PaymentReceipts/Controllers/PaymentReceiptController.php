<?php

namespace App\Modules\PaymentReceipts\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use PDO;

final class PaymentReceiptController extends Controller
{
    private const DEFAULT_HEALTH_INSURANCE_NOTE = '二代健保補充保費：執行業務收入單次給付未達 20,000 元，免扣補充保費；兼職（非經常性）薪資未達 29,500 元，免扣補充保費；若給付金額超過 1,000 萬元，僅就 1,000 萬元計算補充保費。（補充保費費率：2.11%）';
    private const DEFAULT_PAYMENT_NOTE = '費用匯款與領據處理：所有款項將匯入指定帳戶，請確認帳號正確。未來款項將以匯款憑證作為領據，無需另行簽署紙本收據。';
    private const DEFAULT_TAX_NOTE = '所得稅與補充保費代扣：執行業務收入依法預扣 10% 所得稅；如屬純捐助性質（無對價）款項，則不扣繳所得稅與補充保費。';

    public function index(): void
    {
        $this->requirePermission('payment_receipts.view');

        $filters = $this->filters();
        $receipts = $this->receiptRows($filters['where'], $filters['params']);

        $this->render('payment-receipts.index', [
            'title' => '領款收據',
            'section' => '財務會計',
            'active' => 'payment-receipts',
            'receipts' => $receipts,
            'month' => $filters['month'],
            'status' => $filters['status'],
            'keyword' => $filters['keyword'],
            'summary' => $this->summary($receipts),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('payment_receipts.manage');

        $this->render('payment-receipts.create', [
            'title' => '新增領款收據',
            'section' => '財務會計',
            'active' => 'payment-receipts',
            'receipt' => $this->defaultReceipt(),
            'action' => '/payment-receipts',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('payment_receipts.manage');
        $this->validateReceipt('/payment-receipts/create');

        $payload = $this->payload();
        if ($payload['receipt_no'] === null) {
            $payload['receipt_no'] = $this->nextReceiptNo((string) $payload['receipt_date']);
        }

        $id = $this->insertReceipt($payload, null, null, null);
        AuditLog::write('create', 'payment_receipts', 'payment_receipts', $id);
        flash('success', '領款收據已建立。');
        redirect('/payment-receipts/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('payment_receipts.view');

        $this->render('payment-receipts.show', [
            'title' => '領款收據',
            'section' => '財務會計',
            'active' => 'payment-receipts',
            'receipt' => $this->findReceipt((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function printForm(string $id): void
    {
        $this->requirePermission('payment_receipts.view');

        $this->render('payment-receipts.print', [
            'title' => '領款收據列印',
            'section' => '財務會計',
            'active' => 'payment-receipts',
            'receipt' => $this->findReceipt((int) $id),
            'profile' => foundation_profile(),
            'printable' => false,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');
        $receipt = $this->findReceipt((int) $id);
        if ($receipt['status'] === 'voided') {
            flash('error', '已作廢的領款收據不可編輯。');
            redirect('/payment-receipts/' . $id);
        }

        $this->render('payment-receipts.edit', [
            'title' => '編輯領款收據',
            'section' => '財務會計',
            'active' => 'payment-receipts',
            'receipt' => $receipt,
            'action' => '/payment-receipts/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');
        $receipt = $this->findReceipt((int) $id);
        if ($receipt['status'] === 'voided') {
            flash('error', '已作廢的領款收據不可編輯。');
            redirect('/payment-receipts/' . $id);
        }

        $this->validateReceipt('/payment-receipts/' . $id . '/edit', (int) $id);
        $payload = $this->payload();
        Database::pdo()->prepare(
            'UPDATE payment_receipts
             SET receipt_no = :receipt_no,
                 receipt_date = :receipt_date,
                 template_version = :template_version,
                 payee_name = :payee_name,
                 payee_tax_id = :payee_tax_id,
                 household_address = :household_address,
                 phone = :phone,
                 payment_type = :payment_type,
                 bank_name = :bank_name,
                 bank_branch = :bank_branch,
                 bank_account = :bank_account,
                 bank_account_name = :bank_account_name,
                 fee_category = :fee_category,
                 fee_category_other = :fee_category_other,
                 summary = :summary,
                 amount = :amount,
                 remark = :remark,
                 health_insurance_note = :health_insurance_note,
                 payment_note = :payment_note,
                 tax_note = :tax_note,
                 appendix_note = :appendix_note,
                 status = :status,
                 issued_at = CASE WHEN :status_for_issued = "issued" AND issued_at IS NULL THEN :issued_at ELSE issued_at END,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($payload + [
            'status_for_issued' => $payload['status'],
            'issued_at' => now(),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'payment_receipts', 'payment_receipts', (int) $id);
        if ($payload['status'] === 'issued') {
            $this->syncSourceReceiptStatus($this->findReceipt((int) $id), 'issued');
        }
        flash('success', '領款收據已更新。');
        redirect('/payment-receipts/' . $id);
    }

    public function issue(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');
        $receipt = $this->findReceipt((int) $id);
        if ($receipt['status'] === 'voided') {
            flash('error', '已作廢的領款收據不可開立。');
            redirect('/payment-receipts/' . $id);
        }

        Database::pdo()->prepare(
            'UPDATE payment_receipts
             SET status = "issued",
                 issued_at = COALESCE(issued_at, :issued_at),
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'issued_at' => now(),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('issue', 'payment_receipts', 'payment_receipts', (int) $id);
        $this->syncSourceReceiptStatus($receipt, 'issued');
        flash('success', '領款收據已標記為已開立。');
        redirect('/payment-receipts/' . $id);
    }

    public function void(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');
        $receipt = $this->findReceipt((int) $id);

        Database::pdo()->prepare(
            'UPDATE payment_receipts
             SET status = "voided",
                 voided_at = COALESCE(voided_at, :voided_at),
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'voided_at' => now(),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('void', 'payment_receipts', 'payment_receipts', (int) $id);
        $this->syncSourceReceiptStatus($receipt, 'voided');
        flash('success', '領款收據已作廢。');
        redirect('/payment-receipts/' . $id);
    }

    public function createFromIncomeExpense(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');
        $this->requireSourcePermission('income_expenses.view');

        $record = $this->findIncomeExpenseRecord((int) $id);
        if ($record['item_type'] !== 'expense') {
            flash('error', '只有支出紀錄可以產生領款收據。');
            redirect('/income-expenses/' . $id);
        }
        if ($record['status'] === 'voided') {
            flash('error', '已作廢的支出紀錄不可產生領款收據。');
            redirect('/income-expenses/' . $id);
        }

        $existing = $this->activeReceiptForSource('income_expense_records', (int) $record['id']);
        if ($existing) {
            flash('success', '此支出已經有領款收據，已為你開啟既有單據。');
            redirect('/payment-receipts/' . $existing['id']);
        }

        [$category, $categoryOther] = $this->feeCategoryFromText((string) $record['category_name']);
        $paymentType = $this->paymentTypeFromText((string) $record['payment_method']);
        $receiptNo = $this->nextReceiptNo((string) $record['occurred_on']);
        $remark = trim(implode("\n", array_filter([
            '由收支紀錄產生：' . (string) $record['subject'],
            !empty($record['project_name']) ? '專案：' . (string) $record['project_name'] : '',
            (string) ($record['notes'] ?? ''),
        ])));

        $payload = $this->defaultReceipt();
        $payload['receipt_no'] = $receiptNo;
        $payload['receipt_date'] = (string) $record['occurred_on'];
        $payload['payee_name'] = trim((string) ($record['counterparty'] ?: '未填對象'));
        $payload['payee_tax_id'] = $record['counterparty_tax_id'] ?: null;
        $payload['payment_type'] = $paymentType;
        $payload['payment_note'] = $paymentType === 'bank' ? self::DEFAULT_PAYMENT_NOTE : null;
        $payload['fee_category'] = $category;
        $payload['fee_category_other'] = $categoryOther;
        $payload['summary'] = (string) $record['subject'];
        $payload['amount'] = round((float) $record['amount'], 2);
        $payload['remark'] = $remark !== '' ? $remark : null;

        $receiptId = $this->insertReceipt(
            $payload,
            'income_expense_records',
            (int) $record['id'],
            '收支紀錄：' . (string) $record['subject']
        );
        $this->syncSourceReceiptStatus($this->findReceipt($receiptId), 'draft');

        AuditLog::write('create_from_income_expense', 'payment_receipts', 'payment_receipts', $receiptId, [
            'source_type' => 'income_expense_records',
            'source_id' => (int) $record['id'],
        ]);
        flash('success', '已由收支紀錄產生領款收據草稿，請確認備註與領款資料。');
        redirect('/payment-receipts/' . $receiptId . '/edit');
    }

    public function createFromLecturerExpense(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');
        $this->requireSourcePermission('lecturer_expenses.view');

        $expense = $this->findLecturerExpense((int) $id);
        if ($expense['payment_status'] === 'voided') {
            flash('error', '已作廢的講師費用不可產生領款收據。');
            redirect('/lecturer-expenses/' . $id);
        }

        $existing = $this->activeReceiptForSource('lecturer_expenses', (int) $expense['id']);
        if ($existing) {
            flash('success', '此講師費用已經有領款收據，已為你開啟既有單據。');
            redirect('/payment-receipts/' . $existing['id']);
        }

        $paymentType = $this->paymentTypeFromText((string) $expense['payment_method']);
        $amount = (float) $expense['net_total'] > 0 ? (float) $expense['net_total'] : (float) $expense['gross_total'];
        $feeCategory = (float) $expense['lecture_fee'] > 0 ? '鐘點費' : ((float) $expense['transportation_fee'] > 0 ? '交通補貼' : '其他');
        $payeeName = trim((string) ($expense['display_name'] ?: $expense['lecturer_name']));
        $remark = trim(implode("\n", array_filter([
            '由講師支出費用產生：' . (string) $expense['service_title'],
            !empty($expense['service_unit']) ? '承辦單位：' . (string) $expense['service_unit'] : '',
            !empty($expense['activity_name']) ? '活動：' . (string) $expense['activity_name'] : '',
            '應付總額：' . $this->formatMoney((float) $expense['gross_total']) . '；代扣稅額：' . $this->formatMoney((float) $expense['withholding_tax']) . '；實付金額：' . $this->formatMoney($amount),
            (string) ($expense['notes'] ?? ''),
        ])));

        $payload = $this->defaultReceipt();
        $payload['receipt_no'] = $this->nextReceiptNo((string) $expense['expense_date']);
        $payload['receipt_date'] = (string) $expense['expense_date'];
        $payload['payee_name'] = $payeeName !== '' ? $payeeName : '未填講師';
        $payload['payee_tax_id'] = $expense['tax_id'] ?: null;
        $payload['payment_type'] = $paymentType;
        $payload['bank_name'] = $paymentType === 'bank' ? ($expense['lecturer_bank_name'] ?: null) : null;
        $payload['bank_branch'] = $paymentType === 'bank' ? ($expense['lecturer_bank_branch'] ?: null) : null;
        $payload['bank_account'] = $paymentType === 'bank' ? ($expense['lecturer_bank_account_no'] ?: null) : null;
        $payload['bank_account_name'] = $paymentType === 'bank' ? ($expense['lecturer_bank_account_name'] ?: null) : null;
        $payload['payment_note'] = $paymentType === 'bank' ? self::DEFAULT_PAYMENT_NOTE : null;
        $payload['fee_category'] = $feeCategory;
        $payload['fee_category_other'] = $feeCategory === '其他' ? '講師支出費用' : null;
        $payload['summary'] = (string) $expense['service_title'];
        $payload['amount'] = round($amount, 2);
        $payload['remark'] = $remark !== '' ? $remark : null;

        $receiptId = $this->insertReceipt(
            $payload,
            'lecturer_expenses',
            (int) $expense['id'],
            '講師費用：' . (string) $expense['service_title']
        );
        $this->syncSourceReceiptStatus($this->findReceipt($receiptId), 'draft');

        AuditLog::write('create_from_lecturer_expense', 'payment_receipts', 'payment_receipts', $receiptId, [
            'source_type' => 'lecturer_expenses',
            'source_id' => (int) $expense['id'],
        ]);
        flash('success', '已由講師費用產生領款收據草稿，請確認備註與領款資料。');
        redirect('/payment-receipts/' . $receiptId . '/edit');
    }

    private function insertReceipt(array $payload, ?string $sourceType, ?int $sourceId, ?string $sourceLabel): int
    {
        Database::pdo()->prepare(
            'INSERT INTO payment_receipts
             (receipt_no, source_type, source_id, source_label, receipt_date, template_version, payee_name, payee_tax_id, household_address, phone, payment_type, bank_name, bank_branch, bank_account, bank_account_name, fee_category, fee_category_other, summary, amount, remark, health_insurance_note, payment_note, tax_note, appendix_note, status, issued_at, created_by, created_at, updated_at)
             VALUES
             (:receipt_no, :source_type, :source_id, :source_label, :receipt_date, :template_version, :payee_name, :payee_tax_id, :household_address, :phone, :payment_type, :bank_name, :bank_branch, :bank_account, :bank_account_name, :fee_category, :fee_category_other, :summary, :amount, :remark, :health_insurance_note, :payment_note, :tax_note, :appendix_note, :status, :issued_at, :created_by, :created_at, :updated_at)'
        )->execute([
            'receipt_no' => $payload['receipt_no'],
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_label' => $sourceLabel,
            'receipt_date' => $payload['receipt_date'],
            'template_version' => $payload['template_version'],
            'payee_name' => $payload['payee_name'],
            'payee_tax_id' => $payload['payee_tax_id'] ?: null,
            'household_address' => $payload['household_address'] ?: null,
            'phone' => $payload['phone'] ?: null,
            'payment_type' => $payload['payment_type'],
            'bank_name' => $payload['payment_type'] === 'bank' ? ($payload['bank_name'] ?: null) : null,
            'bank_branch' => $payload['payment_type'] === 'bank' ? ($payload['bank_branch'] ?: null) : null,
            'bank_account' => $payload['payment_type'] === 'bank' ? ($payload['bank_account'] ?: null) : null,
            'bank_account_name' => $payload['payment_type'] === 'bank' ? ($payload['bank_account_name'] ?: null) : null,
            'fee_category' => $payload['fee_category'],
            'fee_category_other' => $payload['fee_category_other'] ?: null,
            'summary' => $payload['summary'] ?: null,
            'amount' => round((float) $payload['amount'], 2),
            'remark' => $payload['remark'] ?: null,
            'health_insurance_note' => $payload['health_insurance_note'] ?: null,
            'payment_note' => $payload['payment_type'] === 'bank' ? ($payload['payment_note'] ?: null) : null,
            'tax_note' => $payload['tax_note'] ?: null,
            'appendix_note' => $payload['appendix_note'] ?: null,
            'status' => $payload['status'],
            'issued_at' => $payload['status'] === 'issued' ? now() : null,
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    private function requireSourcePermission(string $permission): void
    {
        if (!Permission::can($permission)) {
            http_response_code(403);
            view('errors.403', ['title' => '沒有權限']);
            exit;
        }
    }

    private function findIncomeExpenseRecord(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT income_expense_records.*,
                    projects.name AS linked_project_name
             FROM income_expense_records
             LEFT JOIN projects ON projects.id = income_expense_records.project_id
             WHERE income_expense_records.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到收支紀錄']);
            exit;
        }

        if (empty($record['project_name']) && !empty($record['linked_project_name'])) {
            $record['project_name'] = $record['linked_project_name'];
        }

        return $record;
    }

    private function findLecturerExpense(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT lecturer_expenses.*, lecturers.name AS lecturer_name, lecturers.display_name,
                    lecturers.tax_id, lecturers.bank_name AS lecturer_bank_name,
                    lecturers.bank_branch AS lecturer_bank_branch,
                    lecturers.bank_account_no AS lecturer_bank_account_no,
                    lecturers.bank_account_name AS lecturer_bank_account_name
             FROM lecturer_expenses
             INNER JOIN lecturers ON lecturers.id = lecturer_expenses.lecturer_id
             WHERE lecturer_expenses.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$expense) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到講師費用']);
            exit;
        }

        return $expense;
    }

    private function activeReceiptForSource(string $sourceType, int $sourceId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, receipt_no, status
             FROM payment_receipts
             WHERE source_type = :source_type
               AND source_id = :source_id
               AND status != "voided"
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

        return $receipt ?: null;
    }

    private function feeCategoryFromText(string $text): array
    {
        $rules = [
            '鐘點費' => ['鐘點', '講師', '授課'],
            '工讀金' => ['工讀'],
            '稿費' => ['稿'],
            '出席費' => ['出席'],
            '評審費' => ['評審'],
            '審查費' => ['審查'],
            '獎助金' => ['獎助', '助學'],
            '獎金' => ['獎金'],
            '支援金' => ['支援'],
            '交通補貼' => ['交通', '車資', '補貼'],
            '人事費' => ['人事', '薪資', '勞健保'],
        ];

        foreach ($rules as $category => $keywords) {
            if ($this->containsAny($text, $keywords)) {
                return [$category, null];
            }
        }

        return ['其他', trim($text) !== '' ? trim($text) : null];
    }

    private function paymentTypeFromText(string $text): string
    {
        return $this->containsAny($text, ['現金', '零用金']) ? 'cash' : 'bank';
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 0);
    }

    private function syncSourceReceiptStatus(array $receipt, string $status): void
    {
        $sourceType = (string) ($receipt['source_type'] ?? '');
        $sourceId = (int) ($receipt['source_id'] ?? 0);
        $receiptNo = (string) ($receipt['receipt_no'] ?? '');
        if ($sourceType === '' || $sourceId <= 0 || $receiptNo === '') {
            return;
        }

        if ($sourceType === 'income_expense_records') {
            $receiptStatus = $status === 'issued' ? 'issued' : ($status === 'voided' ? 'voided' : 'pending');
            Database::pdo()->prepare(
                'UPDATE income_expense_records
                 SET receipt_no = :receipt_no,
                     receipt_status = :receipt_status,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'receipt_no' => $receiptNo,
                'receipt_status' => $receiptStatus,
                'updated_at' => now(),
                'id' => $sourceId,
            ]);
        }

        if ($sourceType === 'lecturer_expenses' && $status !== 'voided') {
            Database::pdo()->prepare(
                'UPDATE lecturer_expenses
                 SET receipt_no = :receipt_no,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'receipt_no' => $receiptNo,
                'updated_at' => now(),
                'id' => $sourceId,
            ]);
        }
    }

    private function defaultReceipt(): array
    {
        return [
            'receipt_no' => '',
            'receipt_date' => date('Y-m-d'),
            'template_version' => '2026年1月23日',
            'payee_name' => '',
            'payee_tax_id' => '',
            'household_address' => '',
            'phone' => '',
            'payment_type' => 'bank',
            'bank_name' => '',
            'bank_branch' => '',
            'bank_account' => '',
            'bank_account_name' => '',
            'fee_category' => '人事費',
            'fee_category_other' => '',
            'summary' => '',
            'amount' => '',
            'remark' => '',
            'health_insurance_note' => self::DEFAULT_HEALTH_INSURANCE_NOTE,
            'payment_note' => self::DEFAULT_PAYMENT_NOTE,
            'tax_note' => self::DEFAULT_TAX_NOTE,
            'appendix_note' => '',
            'status' => 'draft',
        ];
    }

    private function validateReceipt(string $redirectTo, ?int $ignoreId = null): void
    {
        $message = Validator::required($_POST, [
            'receipt_date' => '領款日期',
            'payee_name' => '領款者姓名',
            'fee_category' => '費別',
            'amount' => '金額',
        ]);
        if ($message !== null) {
            $this->backWithInput($redirectTo, $_POST, $message);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['receipt_date'])) {
            $this->backWithInput($redirectTo, $_POST, '領款日期格式不正確。');
        }

        if ((float) ($_POST['amount'] ?? 0) <= 0) {
            $this->backWithInput($redirectTo, $_POST, '金額需大於 0。');
        }

        if (!in_array(($_POST['payment_type'] ?? ''), ['bank', 'cash'], true)) {
            $this->backWithInput($redirectTo, $_POST, '請選擇匯款或現金。');
        }

        if (!in_array(($_POST['status'] ?? 'draft'), ['draft', 'issued'], true)) {
            $this->backWithInput($redirectTo, $_POST, '收據狀態不正確。');
        }

        $receiptNo = trim((string) ($_POST['receipt_no'] ?? ''));
        if ($receiptNo !== '' && $this->receiptNoExists($receiptNo, $ignoreId)) {
            $this->backWithInput($redirectTo, $_POST, '收據編號已存在，請改用其他編號。');
        }
    }

    private function payload(): array
    {
        $paymentType = (string) ($_POST['payment_type'] ?? 'bank');
        $receiptNo = $this->nullable('receipt_no');

        return [
            'receipt_no' => $receiptNo,
            'receipt_date' => (string) $_POST['receipt_date'],
            'template_version' => $this->nullable('template_version') ?: '2026年1月23日',
            'payee_name' => trim((string) $_POST['payee_name']),
            'payee_tax_id' => $this->nullable('payee_tax_id'),
            'household_address' => $this->nullable('household_address'),
            'phone' => $this->nullable('phone'),
            'payment_type' => $paymentType,
            'bank_name' => $paymentType === 'bank' ? $this->nullable('bank_name') : null,
            'bank_branch' => $paymentType === 'bank' ? $this->nullable('bank_branch') : null,
            'bank_account' => $paymentType === 'bank' ? $this->nullable('bank_account') : null,
            'bank_account_name' => $paymentType === 'bank' ? $this->nullable('bank_account_name') : null,
            'fee_category' => trim((string) $_POST['fee_category']),
            'fee_category_other' => $this->nullable('fee_category_other'),
            'summary' => $this->nullable('summary'),
            'amount' => round((float) $_POST['amount'], 2),
            'remark' => $this->nullable('remark'),
            'health_insurance_note' => $this->nullable('health_insurance_note'),
            'payment_note' => $paymentType === 'bank' ? $this->nullable('payment_note') : null,
            'tax_note' => $this->nullable('tax_note'),
            'appendix_note' => $this->nullable('appendix_note'),
            'status' => (string) ($_POST['status'] ?? 'draft'),
        ];
    }

    private function nullable(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value === '' ? null : $value;
    }

    private function filters(): array
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $status = in_array(($_GET['status'] ?? ''), ['draft', 'issued', 'voided'], true)
            ? (string) $_GET['status']
            : '';
        $keyword = trim((string) ($_GET['q'] ?? ''));

        $where = ['DATE_FORMAT(payment_receipts.receipt_date, "%Y-%m") = :month'];
        $params = ['month' => $month];
        if ($status !== '') {
            $where[] = 'payment_receipts.status = :status';
            $params['status'] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(payment_receipts.receipt_no LIKE :keyword
                OR payment_receipts.payee_name LIKE :keyword
                OR payment_receipts.fee_category LIKE :keyword
                OR payment_receipts.summary LIKE :keyword
                OR payment_receipts.source_label LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        return [
            'month' => $month,
            'status' => $status,
            'keyword' => $keyword,
            'where' => $where,
            'params' => $params,
        ];
    }

    private function receiptRows(array $where, array $params): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT payment_receipts.*, users.name AS created_by_name
             FROM payment_receipts
             LEFT JOIN users ON users.id = payment_receipts.created_by
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY payment_receipts.receipt_date DESC, payment_receipts.id DESC'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function summary(array $receipts): array
    {
        $summary = [
            'count' => 0,
            'amount' => 0.0,
            'draft' => 0,
            'issued' => 0,
            'bank' => 0,
            'cash' => 0,
        ];

        foreach ($receipts as $receipt) {
            if ($receipt['status'] !== 'voided') {
                $summary['count']++;
                $summary['amount'] += (float) $receipt['amount'];
            }
            if (isset($summary[$receipt['status']])) {
                $summary[$receipt['status']]++;
            }
            if (isset($summary[$receipt['payment_type']])) {
                $summary[$receipt['payment_type']]++;
            }
        }

        return $summary;
    }

    private function findReceipt(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT payment_receipts.*, users.name AS created_by_name
             FROM payment_receipts
             LEFT JOIN users ON users.id = payment_receipts.created_by
             WHERE payment_receipts.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$receipt) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到領款收據']);
            exit;
        }

        return $receipt;
    }

    private function receiptNoExists(string $receiptNo, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM payment_receipts WHERE receipt_no = :receipt_no';
        $params = ['receipt_no' => $receiptNo];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    private function nextReceiptNo(string $date): string
    {
        $prefix = 'RC' . str_replace('-', '', $date) . '-';
        $stmt = Database::pdo()->prepare(
            'SELECT receipt_no
             FROM payment_receipts
             WHERE receipt_no LIKE :prefix
             ORDER BY receipt_no DESC
             LIMIT 1'
        );
        $stmt->execute(['prefix' => $prefix . '%']);
        $latest = (string) ($stmt->fetchColumn() ?: '');
        $sequence = 1;
        if (preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
