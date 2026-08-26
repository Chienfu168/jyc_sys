<?php

namespace App\Modules\FoundationAssets\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class FoundationAssetController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('foundation_assets.view');

        $status = in_array(($_GET['status'] ?? ''), ['active', 'disposed'], true) ? (string) $_GET['status'] : 'active';
        $registration = in_array(($_GET['registration_status'] ?? ''), ['court_registered', 'not_registered'], true) ? (string) $_GET['registration_status'] : '';
        $keyword = trim((string) ($_GET['q'] ?? ''));

        $where = ['status = :status'];
        $params = ['status' => $status];
        if ($registration !== '') {
            $where[] = 'registration_status = :registration_status';
            $params['registration_status'] = $registration;
        }
        if ($keyword !== '') {
            $where[] = '(asset_name LIKE :keyword OR asset_no LIKE :keyword OR storage_location LIKE :keyword OR acquisition_source LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT *
             FROM foundation_assets
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY registration_status, asset_kind, id DESC'
        );
        $stmt->execute($params);
        $assets = $stmt->fetchAll();

        $this->render('foundation-assets.index', [
            'title' => '財產清冊',
            'section' => '財務會計',
            'active' => 'foundation-assets',
            'assets' => $assets,
            'status' => $status,
            'registration' => $registration,
            'keyword' => $keyword,
            'summary' => $this->summary($assets),
            'profile' => foundation_profile(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('foundation_assets.manage');

        $this->render('foundation-assets.create', [
            'title' => '新增財產',
            'section' => '財務會計',
            'active' => 'foundation-assets',
            'asset' => $this->blankAsset(),
            'action' => '/foundation-assets',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('foundation_assets.manage');
        $this->validateAsset('/foundation-assets/create');

        Database::pdo()->prepare(
            'INSERT INTO foundation_assets
             (registration_status, asset_kind, asset_name, unit, quantity, amount, acquired_on, acquisition_source, storage_location, proof_document, asset_no, notes, status, created_by, created_at, updated_at)
             VALUES
             (:registration_status, :asset_kind, :asset_name, :unit, :quantity, :amount, :acquired_on, :acquisition_source, :storage_location, :proof_document, :asset_no, :notes, :status, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'foundation_assets', 'foundation_assets', $id);
        flash('success', '財產資料已建立。');
        redirect('/foundation-assets/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('foundation_assets.view');

        $this->render('foundation-assets.show', [
            'title' => '財產資料',
            'section' => '財務會計',
            'active' => 'foundation-assets',
            'asset' => $this->findAsset((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('foundation_assets.manage');

        $this->render('foundation-assets.edit', [
            'title' => '編輯財產',
            'section' => '財務會計',
            'active' => 'foundation-assets',
            'asset' => $this->findAsset((int) $id),
            'action' => '/foundation-assets/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('foundation_assets.manage');
        $asset = $this->findAsset((int) $id);
        $this->validateAsset('/foundation-assets/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE foundation_assets
             SET registration_status = :registration_status,
                 asset_kind = :asset_kind,
                 asset_name = :asset_name,
                 unit = :unit,
                 quantity = :quantity,
                 amount = :amount,
                 acquired_on = :acquired_on,
                 acquisition_source = :acquisition_source,
                 storage_location = :storage_location,
                 proof_document = :proof_document,
                 asset_no = :asset_no,
                 notes = :notes,
                 status = :status,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $asset['id'],
        ]);

        AuditLog::write('update', 'foundation_assets', 'foundation_assets', (int) $asset['id']);
        flash('success', '財產資料已更新。');
        redirect('/foundation-assets/' . $id);
    }

    public function dispose(string $id): void
    {
        $this->requirePermission('foundation_assets.manage');
        $asset = $this->findAsset((int) $id);
        $status = $asset['status'] === 'active' ? 'disposed' : 'active';

        Database::pdo()->prepare('UPDATE foundation_assets SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'status' => $status,
                'updated_at' => now(),
                'id' => (int) $asset['id'],
            ]);

        AuditLog::write('dispose', 'foundation_assets', 'foundation_assets', (int) $asset['id']);
        flash('success', $status === 'disposed' ? '財產已標示除帳。' : '財產已恢復使用。');
        redirect('/foundation-assets/' . $id);
    }

    public function destroy(string $id): void
    {
        $asset = $this->findAsset((int) $id);
        $this->requireManageOrOwner('foundation_assets.delete', $asset['created_by'] ?? null);

        Database::pdo()->prepare('DELETE FROM foundation_assets WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'foundation_assets', 'foundation_assets', (int) $id, ['asset_name' => $asset['asset_name'] ?? null]);
        flash('success', '財產已刪除。');
        redirect('/foundation-assets');
    }

    private function validateAsset(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'asset_name' => '財產名稱',
            'registration_status' => '登記狀態',
            'asset_kind' => '財產種類',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!in_array($_POST['registration_status'] ?? '', ['court_registered', 'not_registered'], true)) {
            $this->backWithInput($path, $_POST, '登記狀態不正確。');
        }
        if (!in_array($_POST['asset_kind'] ?? '', ['movable', 'real_estate', 'other'], true)) {
            $this->backWithInput($path, $_POST, '財產種類不正確。');
        }
        if (!in_array($_POST['status'] ?? '', ['active', 'disposed'], true)) {
            $this->backWithInput($path, $_POST, '狀態不正確。');
        }
        if ($this->decimalValue('quantity') < 0 || $this->decimalValue('amount') < 0) {
            $this->backWithInput($path, $_POST, '數量與金額不可小於 0。');
        }
        if ($this->dateOrNull('acquired_on') === null && trim((string) ($_POST['acquired_on'] ?? '')) !== '') {
            $this->backWithInput($path, $_POST, '取得日期格式不正確。');
        }
    }

    private function payload(): array
    {
        return [
            'registration_status' => (string) $_POST['registration_status'],
            'asset_kind' => (string) $_POST['asset_kind'],
            'asset_name' => trim((string) $_POST['asset_name']),
            'unit' => trim((string) ($_POST['unit'] ?? '')),
            'quantity' => $this->decimalValue('quantity') ?: 1,
            'amount' => $this->decimalValue('amount'),
            'acquired_on' => $this->dateOrNull('acquired_on'),
            'acquisition_source' => trim((string) ($_POST['acquisition_source'] ?? '')),
            'storage_location' => trim((string) ($_POST['storage_location'] ?? '')),
            'proof_document' => trim((string) ($_POST['proof_document'] ?? '')),
            'asset_no' => trim((string) ($_POST['asset_no'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'status' => (string) $_POST['status'],
        ];
    }

    private function findAsset(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM foundation_assets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$asset) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到財產資料']);
            exit;
        }

        return $asset;
    }

    private function summary(array $assets): array
    {
        $summary = [
            'court_registered' => 0.0,
            'not_registered' => 0.0,
            'active_count' => count($assets),
            'total' => 0.0,
        ];
        foreach ($assets as $asset) {
            $amount = (float) $asset['amount'];
            $key = $asset['registration_status'] === 'court_registered' ? 'court_registered' : 'not_registered';
            $summary[$key] += $amount;
            $summary['total'] += $amount;
        }

        return $summary;
    }

    private function blankAsset(): array
    {
        return [
            'registration_status' => 'not_registered',
            'asset_kind' => 'movable',
            'asset_name' => '',
            'unit' => '式',
            'quantity' => 1,
            'amount' => '',
            'acquired_on' => '',
            'acquisition_source' => '',
            'storage_location' => '',
            'proof_document' => '',
            'asset_no' => '',
            'notes' => '',
            'status' => 'active',
        ];
    }

    private function decimalValue(string $key): float
    {
        return max(0, round((float) ($_POST[$key] ?? 0), 2));
    }

    private function dateOrNull(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
