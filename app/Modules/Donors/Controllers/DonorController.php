<?php

namespace App\Modules\Donors\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class DonorController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('donors.view');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $type = in_array(($_GET['type'] ?? ''), ['person', 'organization'], true) ? (string) $_GET['type'] : '';
        $status = in_array(($_GET['status'] ?? ''), ['active', 'archived'], true) ? (string) $_GET['status'] : 'active';

        $where = ['donors.status = :status'];
        $params = ['status' => $status];
        if ($type !== '') {
            $where[] = 'donors.donor_type = :type';
            $params['type'] = $type;
        }
        if ($keyword !== '') {
            $where[] = '(donors.name LIKE :keyword OR donors.phone LIKE :keyword OR donors.email LIKE :keyword OR donors.receipt_title LIKE :keyword OR donors.tax_id LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT donors.*,
                    users.name AS created_by_name,
                    COALESCE(donation_stats.donation_count, 0) AS donation_count,
                    COALESCE(donation_stats.donation_total, 0) AS donation_total,
                    donation_stats.last_donated_at
             FROM donors
             LEFT JOIN users ON users.id = donors.created_by
             LEFT JOIN (
                SELECT donor_id,
                       SUM(CASE WHEN receipt_status != "voided" THEN 1 ELSE 0 END) AS donation_count,
                       SUM(CASE WHEN receipt_status != "voided" THEN amount ELSE 0 END) AS donation_total,
                       MAX(CASE WHEN receipt_status != "voided" THEN donated_at ELSE NULL END) AS last_donated_at
                FROM donations
                GROUP BY donor_id
             ) AS donation_stats ON donation_stats.donor_id = donors.id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY donors.updated_at DESC, donors.id DESC'
        );
        $stmt->execute($params);
        $donors = $stmt->fetchAll();

        $this->render('donors.index', [
            'title' => '捐款人管理',
            'section' => '財務會計',
            'active' => 'donors',
            'donors' => $donors,
            'keyword' => $keyword,
            'type' => $type,
            'status' => $status,
            'summary' => $this->summary($donors),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('donors.manage');

        $this->render('donors.create', [
            'title' => '新增捐款人',
            'section' => '財務會計',
            'active' => 'donors',
            'donor' => [
                'donor_type' => 'person',
                'name' => '',
                'phone' => '',
                'email' => '',
                'address' => '',
                'receipt_title' => '',
                'tax_id' => '',
                'notes' => '',
                'status' => 'active',
            ],
            'action' => '/donors',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('donors.manage');
        $this->validateDonor('/donors/create');

        Database::pdo()->prepare(
            'INSERT INTO donors
             (donor_type, name, phone, email, address, receipt_title, tax_id, notes, status, created_by, created_at, updated_at)
             VALUES
             (:donor_type, :name, :phone, :email, :address, :receipt_title, :tax_id, :notes, :status, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'donors', 'donors', $id);
        flash('success', '捐款人已建立。');
        redirect('/donors/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('donors.view');
        $donor = $this->findDonor((int) $id);
        $donations = $this->donations((int) $id);

        $this->render('donors.show', [
            'title' => '捐款人資料',
            'section' => '財務會計',
            'active' => 'donors',
            'donor' => $donor,
            'donations' => $donations,
            'summary' => $this->donationSummary($donations),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('donors.manage');

        $this->render('donors.edit', [
            'title' => '編輯捐款人',
            'section' => '財務會計',
            'active' => 'donors',
            'donor' => $this->findDonor((int) $id),
            'action' => '/donors/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('donors.manage');
        $this->findDonor((int) $id);
        $this->validateDonor('/donors/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE donors
             SET donor_type = :donor_type,
                 name = :name,
                 phone = :phone,
                 email = :email,
                 address = :address,
                 receipt_title = :receipt_title,
                 tax_id = :tax_id,
                 notes = :notes,
                 status = :status,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'donors', 'donors', (int) $id);
        flash('success', '捐款人已更新。');
        redirect('/donors/' . $id);
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('donors.manage');
        $donor = $this->findDonor((int) $id);
        $status = $donor['status'] === 'active' ? 'archived' : 'active';

        Database::pdo()->prepare('UPDATE donors SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'status' => $status,
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        AuditLog::write('toggle_status', 'donors', 'donors', (int) $id, ['status' => $status]);
        flash('success', '捐款人狀態已更新。');
        redirect('/donors/' . $id);
    }

    private function validateDonor(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'name' => '姓名 / 單位名稱',
            'donor_type' => '捐款人類型',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!in_array($_POST['donor_type'] ?? '', ['person', 'organization'], true)) {
            $this->backWithInput($path, $_POST, '捐款人類型不正確。');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithInput($path, $_POST, 'Email 格式不正確。');
        }
    }

    private function payload(): array
    {
        return [
            'donor_type' => (string) $_POST['donor_type'],
            'name' => trim((string) $_POST['name']),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'receipt_title' => trim((string) ($_POST['receipt_title'] ?? '')),
            'tax_id' => trim((string) ($_POST['tax_id'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'status' => in_array($_POST['status'] ?? '', ['active', 'archived'], true) ? (string) $_POST['status'] : 'active',
        ];
    }

    private function findDonor(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT donors.*, users.name AS created_by_name
             FROM donors
             LEFT JOIN users ON users.id = donors.created_by
             WHERE donors.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$donor) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到捐款人']);
            exit;
        }

        return $donor;
    }

    private function donations(int $donorId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT donations.*,
                    accounting_vouchers.voucher_no,
                    accounting_vouchers.status AS voucher_status
             FROM donations
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = donations.accounting_voucher_id
             WHERE donations.donor_id = :donor_id
             ORDER BY donations.donated_at DESC, donations.id DESC'
        );
        $stmt->execute(['donor_id' => $donorId]);

        return $stmt->fetchAll();
    }

    private function summary(array $donors): array
    {
        return [
            'total' => count($donors),
            'amount' => array_sum(array_map(static fn (array $donor): float => (float) $donor['donation_total'], $donors)),
            'donations' => array_sum(array_map(static fn (array $donor): int => (int) $donor['donation_count'], $donors)),
        ];
    }

    private function donationSummary(array $donations): array
    {
        $valid = array_values(array_filter($donations, static fn (array $donation): bool => $donation['receipt_status'] !== 'voided'));

        return [
            'count' => count($valid),
            'amount' => array_sum(array_map(static fn (array $donation): float => (float) $donation['amount'], $valid)),
            'pending_receipts' => count(array_filter($valid, static fn (array $donation): bool => $donation['receipt_status'] === 'pending')),
        ];
    }
}
