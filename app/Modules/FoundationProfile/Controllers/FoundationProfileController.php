<?php

namespace App\Modules\FoundationProfile\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;

final class FoundationProfileController extends Controller
{
    public function edit(): void
    {
        $this->requirePermission('foundation_profile.view');

        $this->render('foundation-profile.edit', [
            'title' => '基金會基本資料',
            'section' => '系統設定',
            'active' => 'foundation-profile',
            'profile' => foundation_profile(),
            'printable' => false,
        ]);
    }

    public function update(): void
    {
        $this->requirePermission('foundation_profile.manage');

        if ($error = Validator::required($_POST, [
            'foundation_name' => '基金會名稱',
        ])) {
            $this->backWithInput('/foundation-profile', $_POST, $error);
        }

        $month = (int) ($_POST['fiscal_year_start_month'] ?? 1);
        if ($month < 1 || $month > 12) {
            $month = 1;
        }

        Database::pdo()->prepare(
            'INSERT INTO foundation_profiles
             (id, foundation_name, english_name, tax_id, registration_no, competent_authority, approval_date, approval_doc_no, representative, executive_director, undertaker, phone, email, website, address, mission, service_area, fiscal_year_start_month, updated_by, created_at, updated_at)
             VALUES
             (1, :foundation_name, :english_name, :tax_id, :registration_no, :competent_authority, :approval_date, :approval_doc_no, :representative, :executive_director, :undertaker, :phone, :email, :website, :address, :mission, :service_area, :fiscal_year_start_month, :updated_by, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
               foundation_name = VALUES(foundation_name),
               english_name = VALUES(english_name),
               tax_id = VALUES(tax_id),
               registration_no = VALUES(registration_no),
               competent_authority = VALUES(competent_authority),
               approval_date = VALUES(approval_date),
               approval_doc_no = VALUES(approval_doc_no),
               representative = VALUES(representative),
               executive_director = VALUES(executive_director),
               undertaker = VALUES(undertaker),
               phone = VALUES(phone),
               email = VALUES(email),
               website = VALUES(website),
               address = VALUES(address),
               mission = VALUES(mission),
               service_area = VALUES(service_area),
               fiscal_year_start_month = VALUES(fiscal_year_start_month),
               updated_by = VALUES(updated_by),
               updated_at = VALUES(updated_at)'
        )->execute([
            'foundation_name' => trim((string) $_POST['foundation_name']),
            'english_name' => trim((string) ($_POST['english_name'] ?? '')),
            'tax_id' => trim((string) ($_POST['tax_id'] ?? '')),
            'registration_no' => trim((string) ($_POST['registration_no'] ?? '')),
            'competent_authority' => trim((string) ($_POST['competent_authority'] ?? '')),
            'approval_date' => $this->dateOrNull('approval_date'),
            'approval_doc_no' => trim((string) ($_POST['approval_doc_no'] ?? '')),
            'representative' => trim((string) ($_POST['representative'] ?? '')),
            'executive_director' => trim((string) ($_POST['executive_director'] ?? '')),
            'undertaker' => trim((string) ($_POST['undertaker'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'website' => trim((string) ($_POST['website'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'mission' => trim((string) ($_POST['mission'] ?? '')),
            'service_area' => trim((string) ($_POST['service_area'] ?? '')),
            'fiscal_year_start_month' => $month,
            'updated_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLog::write('update', 'foundation_profile', 'foundation_profiles', 1);
        flash('success', '基金會基本資料已更新。');
        redirect('/foundation-profile');
    }

    private function dateOrNull(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
