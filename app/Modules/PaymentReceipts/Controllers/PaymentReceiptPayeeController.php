<?php

namespace App\Modules\PaymentReceipts\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

/**
 * 常用領款人管理:維護經常領款者的基本與匯款資料,供領據表單一鍵帶入。
 */
final class PaymentReceiptPayeeController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('payment_receipts.manage');

        $payees = Database::pdo()->query(
            'SELECT * FROM payment_receipt_payees ORDER BY status, sort_order, payee_name'
        )->fetchAll();

        $this->render('payment-receipts.payees.index', [
            'title' => '常用領款人',
            'section' => '財務會計',
            'active' => 'payment-receipts',
            'payees' => $payees,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('payment_receipts.manage');

        $this->render('payment-receipts.payees.create', [
            'title' => '新增常用領款人',
            'section' => '財務會計',
            'active' => 'payment-receipts',
            'payee' => $this->blankPayee(),
            'action' => '/payment-receipt-payees',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('payment_receipts.manage');
        $this->validatePayee('/payment-receipt-payees/create');

        try {
            $params = $this->params();
            $params['created_at'] = now();
            Database::pdo()->prepare(
                'INSERT INTO payment_receipt_payees
                 (payee_name, payee_tax_id, phone, household_address, payment_type,
                  bank_name, bank_branch, bank_account, bank_account_name, fee_category,
                  note, sort_order, status, created_at, updated_at)
                 VALUES
                 (:payee_name, :payee_tax_id, :phone, :household_address, :payment_type,
                  :bank_name, :bank_branch, :bank_account, :bank_account_name, :fee_category,
                  :note, :sort_order, :status, :created_at, :updated_at)'
            )->execute($params);
        } catch (\PDOException) {
            $this->backWithInput('/payment-receipt-payees/create', $_POST, '常用領款人姓名已存在，請改用其他名稱或編輯既有資料。');
        }

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'payment_receipts', 'payment_receipt_payees', $id);
        flash('success', '常用領款人已建立。');
        redirect('/payment-receipt-payees');
    }

    public function edit(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');

        $this->render('payment-receipts.payees.edit', [
            'title' => '編輯常用領款人',
            'section' => '財務會計',
            'active' => 'payment-receipts',
            'payee' => $this->findPayee((int) $id),
            'action' => '/payment-receipt-payees/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');
        $this->findPayee((int) $id);
        $this->validatePayee('/payment-receipt-payees/' . $id . '/edit');

        try {
            $params = $this->params();
            $params['id'] = (int) $id;
            Database::pdo()->prepare(
                'UPDATE payment_receipt_payees
                 SET payee_name = :payee_name,
                     payee_tax_id = :payee_tax_id,
                     phone = :phone,
                     household_address = :household_address,
                     payment_type = :payment_type,
                     bank_name = :bank_name,
                     bank_branch = :bank_branch,
                     bank_account = :bank_account,
                     bank_account_name = :bank_account_name,
                     fee_category = :fee_category,
                     note = :note,
                     sort_order = :sort_order,
                     status = :status,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute($params);
        } catch (\PDOException) {
            $this->backWithInput('/payment-receipt-payees/' . $id . '/edit', $_POST, '常用領款人姓名已存在，請改用其他名稱。');
        }

        AuditLog::write('update', 'payment_receipts', 'payment_receipt_payees', (int) $id);
        flash('success', '常用領款人已更新。');
        redirect('/payment-receipt-payees');
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');
        $payee = $this->findPayee((int) $id);
        $status = $payee['status'] === 'active' ? 'disabled' : 'active';

        Database::pdo()->prepare('UPDATE payment_receipt_payees SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute(['status' => $status, 'updated_at' => now(), 'id' => (int) $id]);

        AuditLog::write('toggle_status', 'payment_receipts', 'payment_receipt_payees', (int) $id, ['status' => $status]);
        flash('success', '常用領款人狀態已更新。');
        redirect('/payment-receipt-payees');
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('payment_receipts.manage');
        $payee = $this->findPayee((int) $id);

        // 常用領款人為參考資料,領據已另存一份領款人資料,刪除不影響既有領據。
        Database::pdo()->prepare('DELETE FROM payment_receipt_payees WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'payment_receipts', 'payment_receipt_payees', (int) $id, [
            'payee_name' => $payee['payee_name'] ?? null,
        ]);
        flash('success', '常用領款人已刪除。');
        redirect('/payment-receipt-payees');
    }

    private function validatePayee(string $path): void
    {
        if ($error = Validator::required($_POST, ['payee_name' => '領款者姓名'])) {
            $this->backWithInput($path, $_POST, $error);
        }
    }

    /** @return array<string, mixed> */
    private function params(): array
    {
        $paymentType = ($_POST['payment_type'] ?? '') === 'cash' ? 'cash' : 'bank';

        return [
            'payee_name' => trim((string) $_POST['payee_name']),
            'payee_tax_id' => $this->nullable('payee_tax_id'),
            'phone' => $this->nullable('phone'),
            'household_address' => $this->nullable('household_address'),
            'payment_type' => $paymentType,
            'bank_name' => $paymentType === 'bank' ? $this->nullable('bank_name') : null,
            'bank_branch' => $paymentType === 'bank' ? $this->nullable('bank_branch') : null,
            'bank_account' => $paymentType === 'bank' ? $this->nullable('bank_account') : null,
            'bank_account_name' => $paymentType === 'bank' ? $this->nullable('bank_account_name') : null,
            'fee_category' => $this->nullable('fee_category'),
            'note' => $this->nullable('note'),
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
            'status' => ($_POST['status'] ?? '') === 'disabled' ? 'disabled' : 'active',
            'updated_at' => now(),
        ];
    }

    private function nullable(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value === '' ? null : $value;
    }

    /** @return array<string, mixed> */
    private function blankPayee(): array
    {
        return [
            'payee_name' => '',
            'payee_tax_id' => '',
            'phone' => '',
            'household_address' => '',
            'payment_type' => 'bank',
            'bank_name' => '',
            'bank_branch' => '',
            'bank_account' => '',
            'bank_account_name' => '',
            'fee_category' => '',
            'note' => '',
            'sort_order' => 10,
            'status' => 'active',
        ];
    }

    /** @return array<string, mixed> */
    private function findPayee(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM payment_receipt_payees WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $payee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payee) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到常用領款人']);
            exit;
        }

        return $payee;
    }
}
