<?php

namespace App\Modules\Donations\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use App\Domain\Donations\DonationListSummary;
use App\Domain\Donations\DonationNumber;
use App\Domain\Donations\ReceiptNumber;
use PDO;

final class DonationController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('donations.view');

        $filters = $this->donationFilters();
        $donations = $this->donationRows($filters['where'], $filters['params']);

        $this->render('donations.index', [
            'title' => '捐款紀錄',
            'section' => '財務會計',
            'active' => 'donations',
            'donations' => $donations,
            'year' => $filters['year'],
            'month' => $filters['month'],
            'receiptStatus' => $filters['receiptStatus'],
            'deliveryStatus' => $filters['deliveryStatus'],
            'keyword' => $filters['keyword'],
            'summary' => $this->summary($donations),
            'canExport' => $this->canExportDonations(),
        ]);
    }

    public function report(): void
    {
        $this->requirePermission('donations.view');

        $year = $this->yearValue($_GET['year'] ?? date('Y'));
        $month = preg_match('/^(0[1-9]|1[0-2])$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : '';
        [$where, $params] = $this->reportDateScope($year, $month);
        $donations = $this->donationRows([$where], $params, 'ASC');
        $undeliveredReceipts = $this->undeliveredReceiptRows($where, $params);

        $statusSummary = $this->groupedDonationSummary(
            'donations.receipt_status',
            $where,
            $params,
            false
        );
        $paymentSummary = $this->groupedDonationSummary(
            'donations.payment_method',
            $where,
            $params
        );
        $projectSummary = $this->groupedDonationSummary(
            'COALESCE(NULLIF(donations.project_name, ""), "未指定")',
            $where,
            $params
        );
        $topDonors = $this->topDonors($where, $params);
        $monthlySummary = $this->monthlySummary($year);

        $this->render('donations.report', [
            'title' => '捐款台帳',
            'section' => '財務會計',
            'active' => 'donations',
            'year' => $year,
            'month' => $month,
            'summary' => $this->summary($donations),
            'statusSummary' => $statusSummary,
            'paymentSummary' => $paymentSummary,
            'projectSummary' => $projectSummary,
            'topDonors' => $topDonors,
            'monthlySummary' => $monthlySummary,
            'undeliveredReceipts' => $undeliveredReceipts,
            'canExport' => $this->canExportDonations(),
            'profile' => foundation_profile(),
        ]);
    }

    public function export(): void
    {
        $this->requireDonationExportPermission();

        $filters = $this->donationFilters();
        $donations = $this->donationRows($filters['where'], $filters['params']);

        AuditLog::write('export', 'donations', 'donations', null, [
            'year' => $filters['year'],
            'month' => $filters['month'],
            'receipt_status' => $filters['receiptStatus'],
            'delivery_status' => $filters['deliveryStatus'],
            'keyword' => $filters['keyword'],
            'count' => count($donations),
        ]);

        $scope = $filters['month'] !== '' ? $filters['year'] . $filters['month'] : (string) $filters['year'];
        $filename = 'donations-' . $scope . '-' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');

        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        if ($output === false) {
            exit;
        }

        fputcsv($output, [
            '捐款編號',
            '捐款日期',
            '捐款人',
            '捐款人類型',
            '收據抬頭',
            '統編/身分證字號',
            '電話',
            'Email',
            '捐款方式',
            '收據狀態',
            '收據號碼',
            '寄送狀態',
            '最近寄送日',
            '指定專案/用途',
            '金額',
            '會計傳票',
            '傳票狀態',
            '備註',
        ]);

        foreach ($donations as $donation) {
            fputcsv($output, [
                $donation['donation_no'] ?? '',
                $donation['donated_at'],
                $donation['donor_name'],
                $this->donorTypeLabel((string) $donation['donor_type']),
                $donation['receipt_title'] ?: $donation['donor_name'],
                $donation['tax_id'] ?: '',
                $donation['donor_phone'] ?: '',
                $donation['donor_email'] ?: '',
                $donation['payment_method'],
                $this->receiptLabel((string) $donation['receipt_status']),
                $donation['receipt_no'] ?: '',
                $this->receiptDeliveryLabel($donation),
                !empty($donation['latest_receipt_delivered_on']) ? roc_date((string) $donation['latest_receipt_delivered_on']) : '',
                $donation['project_name'] ?: '',
                number_format((float) $donation['amount'], 0, '.', ''),
                $donation['voucher_no'] ?: '',
                $this->voucherLabel((string) ($donation['voucher_status'] ?? '')),
                $donation['notes'] ?: '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * 捐贈收入清冊(主管機關核備用):依捐贈單位/人彙總年度捐贈金額,格式參考教育局範例。
     */
    public function incomeList(): void
    {
        $this->requirePermission('donations.view');

        $year = $this->yearValue($_GET['year'] ?? date('Y'));
        [$where, $params] = $this->reportDateScope($year, '');

        $stmt = Database::pdo()->prepare(
            'SELECT donors.name,
                    donors.donor_type,
                    COUNT(donations.id) AS donation_count,
                    SUM(donations.amount) AS total_amount
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             WHERE ' . $where . ' AND donations.receipt_status != "voided"
             GROUP BY donors.id, donors.name, donors.donor_type
             ORDER BY total_amount DESC, donation_count DESC, donors.name'
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $this->render('donations.income-list', [
            'title' => '捐贈收入清冊',
            'section' => '主管機關核備',
            'active' => 'donations',
            'year' => $year,
            'rows' => $rows,
            'totalAmount' => DonationListSummary::total($rows),
            'profile' => foundation_profile(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('donations.manage');

        $this->render('donations.create', [
            'title' => '新增捐款',
            'section' => '財務會計',
            'active' => 'donations',
            'donation' => [
                'donor_id' => (int) ($_GET['donor_id'] ?? 0),
                'donated_at' => date('Y-m-d'),
                'amount' => '',
                'payment_method' => '',
                'receipt_no' => '',
                'receipt_status' => 'pending',
                'project_name' => '',
                'notes' => '',
            ],
            'donors' => $this->donors(),
            'action' => '/donations',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('donations.manage');
        $this->validateDonation('/donations/create');

        $pdo = Database::pdo();
        $payload = $this->payload();

        $pdo->beginTransaction();
        try {
            $donationNo = $this->nextDonationNo($pdo, $payload['donated_at']);
            $pdo->prepare(
                'INSERT INTO donations
                 (donor_id, donated_at, donation_no, amount, payment_method, receipt_no, receipt_status, project_name, notes, created_by, created_at, updated_at)
                 VALUES
                 (:donor_id, :donated_at, :donation_no, :amount, :payment_method, :receipt_no, :receipt_status, :project_name, :notes, :created_by, :created_at, :updated_at)'
            )->execute($payload + [
                'donation_no' => $donationNo,
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $id = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->backWithInput('/donations/create', $_POST, '捐款紀錄建立失敗：' . $e->getMessage());
        }

        AuditLog::write('create', 'donations', 'donations', $id);
        flash('success', '捐款紀錄已建立。');
        redirect('/donations/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('donations.view');

        $this->render('donations.show', [
            'title' => '捐款紀錄',
            'section' => '財務會計',
            'active' => 'donations',
            'donation' => $this->findDonation((int) $id),
            'receiptVoids' => $this->receiptVoids((int) $id),
            'receiptDeliveries' => $this->receiptDeliveries((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function receipt(string $id): void
    {
        $this->requirePermission('donations.view');

        $this->render('donations.receipt', [
            'title' => '捐款收據',
            'section' => '財務會計',
            'active' => 'donations',
            'donation' => $this->findDonation((int) $id),
            'receiptVoids' => $this->receiptVoids((int) $id),
            'receiptDeliveries' => $this->receiptDeliveries((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function printReceipts(): void
    {
        $this->requirePermission('donations.view');

        $filters = $this->donationFiltersFrom($_GET, 'issued');
        $where = $filters['where'];
        $where[] = 'donations.receipt_no IS NOT NULL AND donations.receipt_no != ""';
        $donations = $this->donationRows($where, $filters['params'], 'ASC');

        if (!$donations) {
            flash('error', '目前查詢條件沒有已開立且具收據號碼的收據。');
            redirect($this->donationListUrl(
                $filters['year'],
                $filters['month'],
                'issued',
                $filters['keyword']
            ));
        }

        AuditLog::write('print_receipts', 'donations', 'donations', null, [
            'year' => $filters['year'],
            'month' => $filters['month'],
            'keyword' => $filters['keyword'],
            'count' => count($donations),
        ]);

        $this->render('donations.receipts-print', [
            'title' => '批次列印收據',
            'section' => '財務會計',
            'active' => 'donations',
            'donations' => $donations,
            'year' => $filters['year'],
            'month' => $filters['month'],
            'keyword' => $filters['keyword'],
            'profile' => foundation_profile(),
        ]);
    }

    public function issueReceipt(string $id): void
    {
        $this->requirePermission('donations.manage');
        $donation = $this->findDonation((int) $id);

        if ($donation['receipt_status'] === 'voided') {
            flash('error', '已作廢的捐款不可開立收據。');
            redirect('/donations/' . $id);
        }
        if ($donation['receipt_status'] === 'not_required') {
            flash('error', '此捐款標示為免開收據，請先編輯收據狀態。');
            redirect('/donations/' . $id);
        }
        if ($donation['receipt_status'] === 'issued' && !empty($donation['receipt_no'])) {
            flash('success', '此捐款收據已開立。');
            redirect('/donations/' . $id . '/receipt');
        }

        $pdo = Database::pdo();
        $receiptNo = trim((string) ($donation['receipt_no'] ?? ''));
        $pdo->beginTransaction();
        try {
            if ($receiptNo === '') {
                $receiptNo = $this->nextReceiptNo($pdo, (string) $donation['donated_at']);
            } elseif ($this->receiptNoExists($pdo, $receiptNo, (int) $donation['id'])) {
                throw new \RuntimeException('收據號碼已被其他捐款紀錄使用，請先更正後再開立。');
            }

            $pdo->prepare(
                'UPDATE donations
                 SET receipt_no = :receipt_no,
                     receipt_status = "issued",
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'receipt_no' => $receiptNo,
                'updated_at' => now(),
                'id' => (int) $donation['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            flash('error', '收據開立失敗：' . $exception->getMessage());
            redirect('/donations/' . $id);
        }

        AuditLog::write('issue_receipt', 'donations', 'donations', (int) $donation['id'], [
            'receipt_no' => $receiptNo,
        ]);
        flash('success', '收據已開立：' . $receiptNo);
        redirect('/donations/' . $id . '/receipt');
    }

    public function voidReceipt(string $id): void
    {
        $this->requirePermission('donations.manage');

        $reason = $this->receiptVoidReason();
        if ($reason === '') {
            flash('error', '請填寫收據作廢原因。');
            redirect('/donations/' . $id);
        }

        $pdo = Database::pdo();
        $receiptNo = '';
        $pdo->beginTransaction();
        try {
            $donation = $this->lockDonation($pdo, (int) $id);
            $receiptNo = trim((string) ($donation['receipt_no'] ?? ''));
            if ($donation['receipt_status'] !== 'issued' || $receiptNo === '') {
                throw new \RuntimeException('只有已開立且有號碼的收據可以作廢。');
            }

            $this->recordReceiptVoid($pdo, (int) $donation['id'], $receiptNo, $reason, null);
            $pdo->prepare(
                'UPDATE donations
                 SET receipt_no = NULL,
                     receipt_status = "pending",
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'updated_at' => now(),
                'id' => (int) $donation['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', '收據作廢失敗：' . $exception->getMessage());
            redirect('/donations/' . $id);
        }

        AuditLog::write('void_receipt', 'donations', 'donations', (int) $id, [
            'receipt_no' => $receiptNo,
            'reason' => $reason,
        ]);
        flash('success', '收據已作廢，捐款已回到待處理。');
        redirect('/donations/' . $id);
    }

    public function reissueReceipt(string $id): void
    {
        $this->requirePermission('donations.manage');

        $reason = $this->receiptVoidReason();
        if ($reason === '') {
            flash('error', '請填寫重開原因。');
            redirect('/donations/' . $id);
        }

        $pdo = Database::pdo();
        $oldReceiptNo = '';
        $newReceiptNo = '';
        $pdo->beginTransaction();
        try {
            $donation = $this->lockDonation($pdo, (int) $id);
            $oldReceiptNo = trim((string) ($donation['receipt_no'] ?? ''));
            if ($donation['receipt_status'] !== 'issued' || $oldReceiptNo === '') {
                throw new \RuntimeException('只有已開立且有號碼的收據可以重開。');
            }

            $newReceiptNo = $this->nextReceiptNo($pdo, (string) $donation['donated_at']);
            $this->recordReceiptVoid($pdo, (int) $donation['id'], $oldReceiptNo, $reason, $newReceiptNo);
            $pdo->prepare(
                'UPDATE donations
                 SET receipt_no = :receipt_no,
                     receipt_status = "issued",
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'receipt_no' => $newReceiptNo,
                'updated_at' => now(),
                'id' => (int) $donation['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', '收據重開失敗：' . $exception->getMessage());
            redirect('/donations/' . $id);
        }

        AuditLog::write('reissue_receipt', 'donations', 'donations', (int) $id, [
            'old_receipt_no' => $oldReceiptNo,
            'new_receipt_no' => $newReceiptNo,
            'reason' => $reason,
        ]);
        flash('success', '收據已重開，舊號 ' . $oldReceiptNo . '，新號 ' . $newReceiptNo . '。');
        redirect('/donations/' . $id . '/receipt');
    }

    public function recordReceiptDelivery(string $id): void
    {
        $this->requirePermission('donations.manage');
        $donation = $this->findDonation((int) $id);

        if ($donation['receipt_status'] !== 'issued' || empty($donation['receipt_no'])) {
            flash('error', '只有已開立且有號碼的收據可以記錄寄送。');
            redirect('/donations/' . $id);
        }

        $pdo = Database::pdo();
        if (!$this->receiptDeliveriesTableExists($pdo)) {
            flash('error', '收據寄送資料表尚未建立，請先執行系統更新。');
            redirect('/donations/' . $id);
        }

        $payload = $this->receiptDeliveryPayload();
        if (isset($payload['error'])) {
            flash('error', $payload['error']);
            redirect('/donations/' . $id);
        }

        $pdo->prepare(
            'INSERT INTO donation_receipt_deliveries
             (donation_id, receipt_no, delivery_method, delivered_to, delivered_on, notes, created_by, created_at)
             VALUES
             (:donation_id, :receipt_no, :delivery_method, :delivered_to, :delivered_on, :notes, :created_by, :created_at)'
        )->execute([
            'donation_id' => (int) $donation['id'],
            'receipt_no' => (string) $donation['receipt_no'],
            'delivery_method' => $payload['delivery_method'],
            'delivered_to' => $payload['delivered_to'],
            'delivered_on' => $payload['delivered_on'],
            'notes' => $payload['notes'],
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
        ]);

        AuditLog::write('record_receipt_delivery', 'donations', 'donations', (int) $id, [
            'receipt_no' => (string) $donation['receipt_no'],
            'delivery_method' => $payload['delivery_method'],
            'delivered_on' => $payload['delivered_on'],
            'delivered_to' => $payload['delivered_to'],
        ]);
        flash('success', '收據寄送紀錄已新增。');
        redirect('/donations/' . $id);
    }

    public function bulkIssueReceipts(): void
    {
        $this->requirePermission('donations.manage');

        $filters = $this->donationFiltersFrom($_POST, 'pending');
        $pendingUrl = $this->donationListUrl(
            $filters['year'],
            $filters['month'],
            'pending',
            $filters['keyword']
        );
        $issuedUrl = $this->donationListUrl(
            $filters['year'],
            $filters['month'],
            'issued',
            $filters['keyword']
        );

        $pdo = Database::pdo();
        $issuedReceiptNos = [];
        $pdo->beginTransaction();
        try {
            $donations = $this->donationRowsForUpdate($pdo, $filters['where'], $filters['params']);
            if (!$donations) {
                $pdo->rollBack();
                flash('success', '目前查詢條件沒有待處理收據。');
                redirect($pendingUrl);
            }

            $stmt = $pdo->prepare(
                'UPDATE donations
                 SET receipt_no = :receipt_no,
                     receipt_status = "issued",
                     updated_at = :updated_at
                 WHERE id = :id'
            );

            foreach ($donations as $donation) {
                $receiptNo = trim((string) ($donation['receipt_no'] ?? ''));
                if ($receiptNo === '') {
                    $receiptNo = $this->nextReceiptNo($pdo, (string) $donation['donated_at']);
                } elseif ($this->receiptNoExists($pdo, $receiptNo, (int) $donation['id'])) {
                    throw new \RuntimeException('收據號碼 ' . $receiptNo . ' 已被其他捐款紀錄使用，請先更正後再開立。');
                }

                $stmt->execute([
                    'receipt_no' => $receiptNo,
                    'updated_at' => now(),
                    'id' => (int) $donation['id'],
                ]);
                $issuedReceiptNos[] = $receiptNo;
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', '批次開立收據失敗：' . $exception->getMessage());
            redirect($pendingUrl);
        }

        AuditLog::write('bulk_issue_receipts', 'donations', 'donations', null, [
            'year' => $filters['year'],
            'month' => $filters['month'],
            'keyword' => $filters['keyword'],
            'count' => count($issuedReceiptNos),
            'receipt_no_from' => $issuedReceiptNos[0] ?? null,
            'receipt_no_to' => $issuedReceiptNos[count($issuedReceiptNos) - 1] ?? null,
        ]);
        flash('success', '已批次開立 ' . number_format(count($issuedReceiptNos)) . ' 筆收據。');
        redirect($issuedUrl);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('donations.manage');

        $this->render('donations.edit', [
            'title' => '編輯捐款',
            'section' => '財務會計',
            'active' => 'donations',
            'donation' => $this->findDonation((int) $id),
            'donors' => $this->donors(),
            'action' => '/donations/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('donations.manage');
        $donation = $this->findDonation((int) $id);

        if (!empty($donation['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的捐款紀錄不可直接編輯，請以調整傳票處理。');
            redirect('/donations/' . $id);
        }

        $this->validateDonation('/donations/' . $id . '/edit', (int) $id);

        Database::pdo()->prepare(
            'UPDATE donations
             SET donor_id = :donor_id,
                 donated_at = :donated_at,
                 amount = :amount,
                 payment_method = :payment_method,
                 receipt_no = :receipt_no,
                 receipt_status = :receipt_status,
                 project_name = :project_name,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'donations', 'donations', (int) $id);
        flash('success', '捐款紀錄已更新。');
        redirect('/donations/' . $id);
    }

    public function void(string $id): void
    {
        $this->requirePermission('donations.manage');
        $donation = $this->findDonation((int) $id);

        if (!empty($donation['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的捐款紀錄不可直接作廢，請先建立調整傳票。');
            redirect('/donations/' . $id);
        }

        $pdo = Database::pdo();
        $receiptNo = trim((string) ($donation['receipt_no'] ?? ''));
        $pdo->beginTransaction();
        try {
            if ($donation['receipt_status'] === 'issued' && $receiptNo !== '') {
                $this->recordReceiptVoid($pdo, (int) $donation['id'], $receiptNo, '捐款紀錄作廢', null);
            }

            $pdo->prepare('UPDATE donations SET receipt_status = "voided", updated_at = :updated_at WHERE id = :id')
                ->execute([
                    'updated_at' => now(),
                    'id' => (int) $id,
                ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', '捐款作廢失敗：' . $exception->getMessage());
            redirect('/donations/' . $id);
        }

        AuditLog::write('void', 'donations', 'donations', (int) $id, [
            'receipt_no' => $receiptNo ?: null,
        ]);
        flash('success', '捐款紀錄已作廢。');
        redirect('/donations/' . $id);
    }

    /**
     * 硬刪除整筆捐款紀錄(含收據作廢/寄送等關聯,以外鍵 CASCADE 一併移除)。
     * 一般作業請用「作廢」保留軌跡;刪除為不可復原動作,僅限具 donations.delete 權限者。
     */
    public function destroy(string $id): void
    {
        $this->requirePermission('donations.delete');
        $donation = $this->findDonation((int) $id);

        if (!empty($donation['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的捐款紀錄不可直接刪除，請先處理相關傳票。');
            redirect('/donations/' . $id);
        }

        Database::pdo()->prepare('DELETE FROM donations WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'donations', 'donations', (int) $id, [
            'amount' => $donation['amount'] ?? null,
            'receipt_no' => trim((string) ($donation['receipt_no'] ?? '')) ?: null,
        ]);
        flash('success', '捐款紀錄已刪除。');
        redirect('/donations');
    }

    public function createVoucher(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $donation = $this->findDonation((int) $id);

        if ($donation['receipt_status'] === 'voided') {
            flash('error', '已作廢的捐款不可建立會計傳票。');
            redirect('/donations/' . $id);
        }
        if (!empty($donation['accounting_voucher_id'])) {
            flash('error', '此捐款已建立會計傳票。');
            redirect('/accounting/vouchers/' . $donation['accounting_voucher_id']);
        }

        if ($existing = $this->existingVoucherForSource((int) $donation['id'])) {
            Database::pdo()->prepare('UPDATE donations SET accounting_voucher_id = :voucher_id, updated_at = :updated_at WHERE id = :id')
                ->execute([
                    'voucher_id' => (int) $existing['id'],
                    'updated_at' => now(),
                    'id' => (int) $donation['id'],
                ]);
            flash('success', '已連結既有捐款會計傳票。');
            redirect('/accounting/vouchers/' . $existing['id']);
        }

        $cashAccount = $this->accountByCode('1100');
        $incomeAccount = $this->accountByCode('4100');
        if (!$cashAccount || !$incomeAccount) {
            flash('error', '找不到捐款拋轉所需會計科目，請先確認 1100 與 4100 已建立。');
            redirect('/donations/' . $id);
        }

        $amount = round((float) $donation['amount'], 2);
        $summary = '捐款收入：' . $donation['donor_name'];
        $voucherId = 0;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO accounting_vouchers
                 (voucher_no, voucher_date, source_type, source_id, summary, status, notes, created_by, created_at, updated_at)
                 VALUES
                 (:voucher_no, :voucher_date, "donations", :source_id, :summary, "draft", :notes, :created_by, :created_at, :updated_at)'
            )->execute([
                'voucher_no' => $this->nextVoucherNo((string) $donation['donated_at']),
                'voucher_date' => $donation['donated_at'],
                'source_id' => (int) $donation['id'],
                'summary' => $summary,
                'notes' => trim('由捐款紀錄自動拋轉' . "\n" . ($donation['notes'] ?? '')),
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $voucherId = (int) $pdo->lastInsertId();

            $lineStmt = $pdo->prepare(
                'INSERT INTO accounting_voucher_lines
                 (voucher_id, account_id, description, debit, credit, sort_order, created_at, updated_at)
                 VALUES
                 (:voucher_id, :account_id, :description, :debit, :credit, :sort_order, :created_at, :updated_at)'
            );
            $this->insertVoucherLine($lineStmt, $voucherId, (int) $cashAccount['id'], $summary, $amount, 0, 10);
            $this->insertVoucherLine($lineStmt, $voucherId, (int) $incomeAccount['id'], $summary, 0, $amount, 20);

            $pdo->prepare(
                'UPDATE donations
                 SET accounting_voucher_id = :accounting_voucher_id, updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'accounting_voucher_id' => $voucherId,
                'updated_at' => now(),
                'id' => (int) $donation['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            flash('error', '會計傳票建立失敗：' . $exception->getMessage());
            redirect('/donations/' . $id);
        }

        AuditLog::write('create_voucher', 'donations', 'accounting_vouchers', $voucherId, [
            'source' => 'donations',
            'source_id' => (int) $donation['id'],
        ]);
        flash('success', '已建立捐款草稿會計傳票，請檢查後過帳。');
        redirect('/accounting/vouchers/' . $voucherId);
    }

    private function validateDonation(string $path, ?int $ignoreId = null): void
    {
        if ($error = Validator::required($_POST, [
            'donor_id' => '捐款人',
            'donated_at' => '捐款日期',
            'amount' => '金額',
            'payment_method' => '捐款方式',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['donated_at'])) {
            $this->backWithInput($path, $_POST, '捐款日期格式不正確。');
        }
        if ($this->amountValue() <= 0) {
            $this->backWithInput($path, $_POST, '金額必須大於 0。');
        }
        if (!$this->donorExists((int) ($_POST['donor_id'] ?? 0))) {
            $this->backWithInput($path, $_POST, '選擇的捐款人不存在。');
        }
        if (!in_array($_POST['receipt_status'] ?? '', ['not_required', 'pending', 'issued', 'voided'], true)) {
            $this->backWithInput($path, $_POST, '收據狀態不正確。');
        }

        $receiptStatus = (string) ($_POST['receipt_status'] ?? 'pending');
        $receiptNo = trim((string) ($_POST['receipt_no'] ?? ''));
        if ($receiptStatus === 'issued' && $receiptNo === '') {
            $this->backWithInput($path, $_POST, '已開立收據必須填寫收據號碼，或先儲存為待處理後使用開立收據。');
        }
        if ($receiptNo !== '' && $this->receiptNoExists(Database::pdo(), $receiptNo, $ignoreId)) {
            $this->backWithInput($path, $_POST, '收據號碼已被其他捐款紀錄使用。');
        }
    }

    private function payload(): array
    {
        return [
            'donor_id' => (int) $_POST['donor_id'],
            'donated_at' => (string) $_POST['donated_at'],
            'amount' => $this->amountValue(),
            'payment_method' => trim((string) $_POST['payment_method']),
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'receipt_status' => (string) ($_POST['receipt_status'] ?? 'pending'),
            'project_name' => trim((string) ($_POST['project_name'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function donationFilters(): array
    {
        return $this->donationFiltersFrom($_GET);
    }

    private function donationFiltersFrom(array $input, ?string $forcedReceiptStatus = null): array
    {
        $validReceiptStatuses = ['not_required', 'pending', 'issued', 'voided'];
        $validDeliveryStatuses = ['delivered', 'undelivered'];
        $yearInput = is_scalar($input['year'] ?? null) ? $input['year'] : date('Y');
        $monthInput = is_scalar($input['month'] ?? null) ? (string) $input['month'] : '';
        $receiptStatusInput = is_scalar($input['receipt_status'] ?? null) ? (string) $input['receipt_status'] : '';
        $deliveryStatusInput = is_scalar($input['delivery_status'] ?? null) ? (string) $input['delivery_status'] : '';
        $keyword = trim(is_scalar($input['q'] ?? null) ? (string) $input['q'] : '');

        $year = $this->yearValue($yearInput);
        $month = preg_match('/^(0[1-9]|1[0-2])$/', $monthInput)
            ? $monthInput
            : '';
        $receiptStatus = in_array($forcedReceiptStatus, $validReceiptStatuses, true)
            ? (string) $forcedReceiptStatus
            : (in_array($receiptStatusInput, $validReceiptStatuses, true) ? $receiptStatusInput : '');
        $deliveryStatus = in_array($deliveryStatusInput, $validDeliveryStatuses, true) ? $deliveryStatusInput : '';

        $where = ['YEAR(donations.donated_at) = :year'];
        $params = ['year' => $year];
        if ($month !== '') {
            $where[] = 'DATE_FORMAT(donations.donated_at, "%Y-%m") = :month';
            $params['month'] = $year . '-' . $month;
        }
        if ($receiptStatus !== '') {
            $where[] = 'donations.receipt_status = :receipt_status';
            $params['receipt_status'] = $receiptStatus;
        }
        if ($deliveryStatus !== '') {
            $where[] = 'donations.receipt_status = "issued"';
            $where[] = 'donations.receipt_no IS NOT NULL AND donations.receipt_no != ""';
            if ($this->receiptDeliveriesTableExists(Database::pdo())) {
                $receiptDeliveryExists = 'EXISTS (
                    SELECT 1
                    FROM donation_receipt_deliveries
                    WHERE donation_receipt_deliveries.donation_id = donations.id
                      AND donation_receipt_deliveries.receipt_no = donations.receipt_no
                )';
                $where[] = $deliveryStatus === 'delivered'
                    ? $receiptDeliveryExists
                    : 'NOT ' . $receiptDeliveryExists;
            } elseif ($deliveryStatus === 'delivered') {
                $where[] = '1 = 0';
            }
        }
        if ($keyword !== '') {
            $where[] = '(donors.name LIKE :keyword OR donors.receipt_title LIKE :keyword OR donors.tax_id LIKE :keyword OR donations.receipt_no LIKE :keyword OR donations.project_name LIKE :keyword OR donations.payment_method LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        return [
            'year' => $year,
            'month' => $month,
            'receiptStatus' => $receiptStatus,
            'deliveryStatus' => $deliveryStatus,
            'keyword' => $keyword,
            'where' => $where,
            'params' => $params,
        ];
    }

    private function donationRows(array $where, array $params, string $direction = 'DESC'): array
    {
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $pdo = Database::pdo();
        $hasReceiptDeliveries = $this->receiptDeliveriesTableExists($pdo);
        $deliveryColumns = $hasReceiptDeliveries
            ? 'COALESCE(receipt_delivery_summary.delivery_count, 0) AS receipt_delivery_count,
                    receipt_delivery_summary.latest_delivered_on AS latest_receipt_delivered_on'
            : '0 AS receipt_delivery_count,
                    NULL AS latest_receipt_delivered_on';
        $deliveryJoin = $hasReceiptDeliveries
            ? 'LEFT JOIN (
                    SELECT donation_id,
                           receipt_no,
                           COUNT(*) AS delivery_count,
                           MAX(delivered_on) AS latest_delivered_on
                    FROM donation_receipt_deliveries
                    GROUP BY donation_id, receipt_no
                ) receipt_delivery_summary
                  ON receipt_delivery_summary.donation_id = donations.id
                 AND receipt_delivery_summary.receipt_no = donations.receipt_no'
            : '';
        $stmt = $pdo->prepare(
            'SELECT donations.*,
                    donors.name AS donor_name,
                    donors.donor_type,
                    donors.receipt_title,
                    donors.tax_id,
                    donors.address AS donor_address,
                    donors.phone AS donor_phone,
                    donors.email AS donor_email,
                    accounting_vouchers.voucher_no,
                    accounting_vouchers.status AS voucher_status,
                    ' . $deliveryColumns . '
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = donations.accounting_voucher_id
             ' . $deliveryJoin . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY donations.donated_at ' . $direction . ', donations.id ' . $direction
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function donationRowsForUpdate(PDO $pdo, array $where, array $params): array
    {
        $lockClause = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $stmt = $pdo->prepare(
            'SELECT donations.id,
                    donations.donated_at,
                    donations.receipt_no
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY donations.donated_at ASC, donations.id ASC' . $lockClause
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function lockDonation(PDO $pdo, int $id): array
    {
        $lockClause = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $stmt = $pdo->prepare(
            'SELECT id, donated_at, receipt_no, receipt_status
             FROM donations
             WHERE id = :id
             LIMIT 1' . $lockClause
        );
        $stmt->execute(['id' => $id]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$donation) {
            throw new \RuntimeException('找不到捐款紀錄。');
        }

        return $donation;
    }

    private function donationListUrl(int $year, string $month, string $receiptStatus, string $keyword): string
    {
        $query = [
            'year' => $year,
            'receipt_status' => $receiptStatus,
            'q' => $keyword,
        ];
        if ($month !== '') {
            $query['month'] = $month;
        }

        return '/donations?' . http_build_query($query);
    }

    private function yearValue(mixed $value): int
    {
        $year = normalize_fiscal_year($value);
        if ($year < 1912 || $year > 2100) {
            return (int) date('Y');
        }

        return $year;
    }

    private function reportDateScope(int $year, string $month): array
    {
        if ($month !== '') {
            return [
                'DATE_FORMAT(donations.donated_at, "%Y-%m") = :report_month',
                ['report_month' => $year . '-' . $month],
            ];
        }

        return [
            'YEAR(donations.donated_at) = :report_year',
            ['report_year' => $year],
        ];
    }

    private function groupedDonationSummary(string $groupExpression, string $where, array $params, bool $excludeVoided = true): array
    {
        $voidedClause = $excludeVoided ? ' AND donations.receipt_status != "voided"' : '';
        $stmt = Database::pdo()->prepare(
            'SELECT ' . $groupExpression . ' AS group_name,
                    COUNT(*) AS donation_count,
                    SUM(donations.amount) AS total_amount
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             WHERE ' . $where . $voidedClause . '
             GROUP BY group_name
             ORDER BY total_amount DESC, group_name'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function topDonors(string $where, array $params): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT donors.id,
                    donors.name,
                    donors.donor_type,
                    COUNT(donations.id) AS donation_count,
                    SUM(donations.amount) AS total_amount,
                    MAX(donations.donated_at) AS last_donated_at
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             WHERE ' . $where . ' AND donations.receipt_status != "voided"
             GROUP BY donors.id, donors.name, donors.donor_type
             ORDER BY total_amount DESC, donation_count DESC, donors.name
             LIMIT 10'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function monthlySummary(int $year): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT MONTH(donations.donated_at) AS month_no,
                    COUNT(*) AS donation_count,
                    SUM(donations.amount) AS total_amount
             FROM donations
             WHERE YEAR(donations.donated_at) = :year
               AND donations.receipt_status != "voided"
             GROUP BY month_no
             ORDER BY month_no'
        );
        $stmt->execute(['year' => $year]);
        $rows = $stmt->fetchAll();

        $summary = [];
        for ($month = 1; $month <= 12; $month++) {
            $summary[$month] = [
                'month_no' => $month,
                'donation_count' => 0,
                'total_amount' => 0,
            ];
        }

        foreach ($rows as $row) {
            $month = (int) $row['month_no'];
            if (isset($summary[$month])) {
                $summary[$month] = $row;
            }
        }

        return array_values($summary);
    }

    private function undeliveredReceiptRows(string $where, array $params): array
    {
        $pdo = Database::pdo();
        $hasReceiptDeliveries = $this->receiptDeliveriesTableExists($pdo);
        $deliveryJoin = $hasReceiptDeliveries
            ? 'LEFT JOIN donation_receipt_deliveries
                  ON donation_receipt_deliveries.donation_id = donations.id
                 AND donation_receipt_deliveries.receipt_no = donations.receipt_no'
            : '';
        $deliveryWhere = $hasReceiptDeliveries ? 'AND donation_receipt_deliveries.id IS NULL' : '';

        $stmt = $pdo->prepare(
            'SELECT donations.id,
                    donations.donor_id,
                    donations.donated_at,
                    donations.amount,
                    donations.receipt_no,
                    donors.name AS donor_name,
                    donors.email AS donor_email,
                    donors.address AS donor_address
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             ' . $deliveryJoin . '
             WHERE ' . $where . '
               AND donations.receipt_status = "issued"
               AND donations.receipt_no IS NOT NULL
               AND donations.receipt_no != ""
               ' . $deliveryWhere . '
             ORDER BY donations.donated_at ASC, donations.id ASC'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function findDonation(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT donations.*,
                    donors.name AS donor_name,
                    donors.receipt_title,
                    donors.tax_id,
                    donors.phone AS donor_phone,
                    donors.email AS donor_email,
                    donors.address AS donor_address,
                    users.name AS created_by_name,
                    accounting_vouchers.voucher_no,
                    accounting_vouchers.status AS voucher_status
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             LEFT JOIN users ON users.id = donations.created_by
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = donations.accounting_voucher_id
             WHERE donations.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$donation) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到捐款紀錄']);
            exit;
        }

        return $donation;
    }

    private function receiptVoids(int $donationId): array
    {
        $pdo = Database::pdo();
        if (!$this->receiptVoidsTableExists($pdo)) {
            return [];
        }

        $stmt = $pdo->prepare(
            'SELECT donation_receipt_voids.*,
                    users.name AS voided_by_name
             FROM donation_receipt_voids
             LEFT JOIN users ON users.id = donation_receipt_voids.voided_by
             WHERE donation_receipt_voids.donation_id = :donation_id
             ORDER BY donation_receipt_voids.voided_at DESC, donation_receipt_voids.id DESC'
        );
        $stmt->execute(['donation_id' => $donationId]);

        return $stmt->fetchAll();
    }

    private function receiptDeliveries(int $donationId): array
    {
        $pdo = Database::pdo();
        if (!$this->receiptDeliveriesTableExists($pdo)) {
            return [];
        }

        $stmt = $pdo->prepare(
            'SELECT donation_receipt_deliveries.*,
                    users.name AS created_by_name
             FROM donation_receipt_deliveries
             LEFT JOIN users ON users.id = donation_receipt_deliveries.created_by
             WHERE donation_receipt_deliveries.donation_id = :donation_id
             ORDER BY donation_receipt_deliveries.delivered_on DESC,
                      donation_receipt_deliveries.id DESC'
        );
        $stmt->execute(['donation_id' => $donationId]);

        return $stmt->fetchAll();
    }

    private function donors(): array
    {
        return Database::pdo()->query(
            'SELECT id, donor_type, name, receipt_title, tax_id, status
             FROM donors
             ORDER BY FIELD(status, "active", "archived"), name'
        )->fetchAll();
    }

    private function donorExists(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM donors WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    private function accountByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id, code, name FROM accounting_accounts WHERE code = :code AND status = "active" LIMIT 1');
        $stmt->execute(['code' => $code]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        return $account ?: null;
    }

    private function existingVoucherForSource(int $donationId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, voucher_no
             FROM accounting_vouchers
             WHERE source_type = "donations" AND source_id = :source_id
             LIMIT 1'
        );
        $stmt->execute(['source_id' => $donationId]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        return $voucher ?: null;
    }

    private function insertVoucherLine(\PDOStatement $stmt, int $voucherId, int $accountId, string $description, float $debit, float $credit, int $sortOrder): void
    {
        $stmt->execute([
            'voucher_id' => $voucherId,
            'account_id' => $accountId,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nextVoucherNo(string $date): string
    {
        $prefix = 'V' . str_replace('-', '', $date) . '-';
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM accounting_vouchers WHERE voucher_no LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '%']);

        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * 依捐款日期產生下一個捐款編號「YYYYMMDD-NNN」;以當日既有編號的最大序號 +1,
     * 遇唯一鍵衝突時往後遞增,避免重複。
     */
    private function nextDonationNo(PDO $pdo, string $date): string
    {
        $prefix = DonationNumber::prefix($date);
        $stmt = $pdo->prepare('SELECT donation_no FROM donations WHERE donation_no LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '%']);
        $current = DonationNumber::maxSequence($stmt->fetchAll(PDO::FETCH_COLUMN), $date);

        do {
            $current++;
            $donationNo = DonationNumber::format($date, $current);
            $check = $pdo->prepare('SELECT 1 FROM donations WHERE donation_no = :donation_no LIMIT 1');
            $check->execute(['donation_no' => $donationNo]);
        } while ($check->fetchColumn());

        return $donationNo;
    }

    private function nextReceiptNo(PDO $pdo, string $date): string
    {
        $year = ReceiptNumber::year($date);
        $prefix = ReceiptNumber::prefix($year);
        $existingMax = $this->maxExistingReceiptNumber($pdo, $year, $prefix);

        $pdo->prepare(
            'INSERT IGNORE INTO donation_receipt_sequences
             (receipt_year, current_no, updated_by, updated_at)
             VALUES
             (:receipt_year, :current_no, :updated_by, :updated_at)'
        )->execute([
            'receipt_year' => $year,
            'current_no' => $existingMax,
            'updated_by' => auth()->user()['id'] ?? null,
            'updated_at' => now(),
        ]);

        $stmt = $pdo->prepare('SELECT current_no FROM donation_receipt_sequences WHERE receipt_year = :receipt_year FOR UPDATE');
        $stmt->execute(['receipt_year' => $year]);
        $current = max((int) $stmt->fetchColumn(), $existingMax);

        do {
            $current++;
            $receiptNo = ReceiptNumber::format($year, $current);
        } while ($this->receiptNoExists($pdo, $receiptNo));

        $pdo->prepare(
            'UPDATE donation_receipt_sequences
             SET current_no = :current_no,
                 updated_by = :updated_by,
                 updated_at = :updated_at
             WHERE receipt_year = :receipt_year'
        )->execute([
            'current_no' => $current,
            'updated_by' => auth()->user()['id'] ?? null,
            'updated_at' => now(),
            'receipt_year' => $year,
        ]);

        return $receiptNo;
    }

    private function maxExistingReceiptNumber(PDO $pdo, int $year, string $prefix): int
    {
        $sql = 'SELECT receipt_no FROM donations WHERE receipt_no LIKE :prefix';
        $params = ['prefix' => $prefix . '%'];
        if ($this->receiptVoidsTableExists($pdo)) {
            $sql .= ' UNION ALL SELECT receipt_no FROM donation_receipt_voids WHERE receipt_no LIKE :void_prefix';
            $params['void_prefix'] = $prefix . '%';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ReceiptNumber::maxSequence($stmt->fetchAll(PDO::FETCH_COLUMN), $year);
    }

    private function receiptNoExists(PDO $pdo, string $receiptNo, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM donations WHERE receipt_no = :receipt_no';
        $params = ['receipt_no' => $receiptNo];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetchColumn()) {
            return true;
        }

        if (!$this->receiptVoidsTableExists($pdo)) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT id FROM donation_receipt_voids WHERE receipt_no = :receipt_no LIMIT 1');
        $stmt->execute(['receipt_no' => $receiptNo]);

        return (bool) $stmt->fetchColumn();
    }

    private function receiptVoidsTableExists(PDO $pdo): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name'
            );
            $stmt->execute(['table_name' => 'donation_receipt_voids']);
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }

    private function receiptDeliveriesTableExists(PDO $pdo): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name'
            );
            $stmt->execute(['table_name' => 'donation_receipt_deliveries']);
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }

    private function recordReceiptVoid(PDO $pdo, int $donationId, string $receiptNo, string $reason, ?string $reissuedReceiptNo): void
    {
        if (!$this->receiptVoidsTableExists($pdo)) {
            throw new \RuntimeException('收據作廢資料表尚未建立，請先執行系統更新。');
        }

        $pdo->prepare(
            'INSERT INTO donation_receipt_voids
             (donation_id, receipt_no, reason, reissued_receipt_no, voided_by, voided_at, created_at)
             VALUES
             (:donation_id, :receipt_no, :reason, :reissued_receipt_no, :voided_by, :voided_at, :created_at)'
        )->execute([
            'donation_id' => $donationId,
            'receipt_no' => $receiptNo,
            'reason' => $reason,
            'reissued_receipt_no' => $reissuedReceiptNo,
            'voided_by' => auth()->user()['id'] ?? null,
            'voided_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function receiptVoidReason(): string
    {
        return trim(is_scalar($_POST['reason'] ?? null) ? (string) $_POST['reason'] : '');
    }

    private function receiptDeliveryPayload(): array
    {
        $method = is_scalar($_POST['delivery_method'] ?? null) ? (string) $_POST['delivery_method'] : '';
        $validMethods = ['email', 'post', 'in_person', 'pickup', 'other'];
        if (!in_array($method, $validMethods, true)) {
            return ['error' => '寄送方式不正確。'];
        }

        $deliveredOn = trim(is_scalar($_POST['delivered_on'] ?? null) ? (string) $_POST['delivered_on'] : '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveredOn)) {
            return ['error' => '寄送日期格式不正確。'];
        }

        $deliveredTo = trim(is_scalar($_POST['delivered_to'] ?? null) ? (string) $_POST['delivered_to'] : '');
        if (mb_strlen($deliveredTo) > 190) {
            return ['error' => '收件對象不可超過 190 個字。'];
        }

        return [
            'delivery_method' => $method,
            'delivered_to' => $deliveredTo,
            'delivered_on' => $deliveredOn,
            'notes' => trim(is_scalar($_POST['notes'] ?? null) ? (string) $_POST['notes'] : ''),
        ];
    }

    private function amountValue(): float
    {
        return round((float) ($_POST['amount'] ?? 0), 2);
    }

    private function requireDonationExportPermission(): void
    {
        $this->requireAuth();

        if (!$this->canExportDonations()) {
            http_response_code(403);
            view('errors.403', ['title' => '沒有權限']);
            exit;
        }
    }

    private function canExportDonations(): bool
    {
        return Permission::can('reports.export') || Permission::can('donations.manage');
    }

    private function receiptLabel(string $status): string
    {
        return ['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
    }

    private function receiptDeliveryLabel(array $donation): string
    {
        if ((string) ($donation['receipt_status'] ?? '') !== 'issued' || trim((string) ($donation['receipt_no'] ?? '')) === '') {
            return '-';
        }

        return (int) ($donation['receipt_delivery_count'] ?? 0) > 0 ? '已寄送' : '未寄送';
    }

    private function voucherLabel(string $status): string
    {
        return ['draft' => '草稿', 'posted' => '已入帳', 'voided' => '已作廢'][$status] ?? $status;
    }

    private function donorTypeLabel(string $type): string
    {
        return ['person' => '個人', 'organization' => '組織'][$type] ?? $type;
    }

    private function summary(array $donations): array
    {
        $valid = array_values(array_filter($donations, static fn (array $donation): bool => $donation['receipt_status'] !== 'voided'));
        $issuedWithReceipt = static fn (array $donation): bool => $donation['receipt_status'] === 'issued'
            && trim((string) ($donation['receipt_no'] ?? '')) !== '';

        return [
            'count' => count($valid),
            'amount' => array_sum(array_map(static fn (array $donation): float => (float) $donation['amount'], $valid)),
            'pending_receipts' => count(array_filter($valid, static fn (array $donation): bool => $donation['receipt_status'] === 'pending')),
            'issued_receipts' => count(array_filter($valid, $issuedWithReceipt)),
            'undelivered_receipts' => count(array_filter(
                $valid,
                static fn (array $donation): bool => $issuedWithReceipt($donation) && (int) ($donation['receipt_delivery_count'] ?? 0) === 0
            )),
            'delivered_receipts' => count(array_filter(
                $valid,
                static fn (array $donation): bool => $issuedWithReceipt($donation) && (int) ($donation['receipt_delivery_count'] ?? 0) > 0
            )),
        ];
    }
}
