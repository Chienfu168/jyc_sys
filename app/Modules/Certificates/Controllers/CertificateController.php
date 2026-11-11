<?php

namespace App\Modules\Certificates\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use App\Domain\Certificates\CertificateFrameStyles;
use App\Domain\Certificates\CertificateTemplates;
use PDO;

/**
 * 聘書／感謝狀:董事聘書、基金會講師聘書、樹林社區大學校長聘書、感謝狀
 * 四種固定範本,開立時挑選範本與彈性文字版本、填入姓名與(視範本而定)
 * 屆次/聘期即自動產生正文,正文仍可自由編修後再存檔,供查詢與重印。
 */
final class CertificateController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('certificates.view');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $templateKey = (string) ($_GET['template'] ?? '');
        if (!CertificateTemplates::exists($templateKey)) {
            $templateKey = '';
        }

        $where = [];
        $params = [];
        if ($keyword !== '') {
            $where[] = '(recipient_name LIKE :kw OR body_text LIKE :kw2)';
            $like = '%' . $keyword . '%';
            $params += ['kw' => $like, 'kw2' => $like];
        }
        if ($templateKey !== '') {
            $where[] = 'template_key = :template_key';
            $params['template_key'] = $templateKey;
        }

        $sql = 'SELECT * FROM certificates';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY issued_on DESC, id DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $certificates = $stmt->fetchAll();

        $this->render('certificates.index', [
            'title' => '聘書與感謝狀',
            'section' => '業務推動',
            'active' => 'certificates',
            'certificates' => $certificates,
            'keyword' => $keyword,
            'templateKey' => $templateKey,
            'templateOptions' => CertificateTemplates::templateOptions(),
            'canManage' => Permission::can('certificates.manage'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('certificates.manage');

        $this->render('certificates.form', [
            'title' => '開立聘書／感謝狀',
            'section' => '業務推動',
            'active' => 'certificates',
            'certificate' => $this->blankCertificate(),
            'templates' => CertificateTemplates::all(),
            'frameStyleOptions' => CertificateFrameStyles::options(),
            'action' => '/certificates',
            'preserveBody' => false,
        ]);
    }

    /** 以既有紀錄為底稿,預帶範本/版本/正文(常用於同活動多人各開一張,僅需改姓名)。 */
    public function duplicate(string $id): void
    {
        $this->requirePermission('certificates.manage');
        $source = $this->findCertificate((int) $id);
        $source['recipient_name'] = '';
        $source['issued_on'] = date('Y-m-d');

        $this->render('certificates.form', [
            'title' => '複製開立聘書／感謝狀',
            'section' => '業務推動',
            'active' => 'certificates',
            'certificate' => $source,
            'templates' => CertificateTemplates::all(),
            'frameStyleOptions' => CertificateFrameStyles::options(),
            'action' => '/certificates',
            'preserveBody' => true,
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('certificates.manage');
        $this->validateCertificate('/certificates/create');

        Database::pdo()->prepare(
            'INSERT INTO certificates
             (template_key, variant_key, title, frame_style, recipient_name, body_text, term_no, term_start, term_end,
              issued_on, signer_title, signer_name, notes, created_by, created_at, updated_at)
             VALUES
             (:template_key, :variant_key, :title, :frame_style, :recipient_name, :body_text, :term_no, :term_start, :term_end,
              :issued_on, :signer_title, :signer_name, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => $this->currentUserId() ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'certificates', 'certificates', $id);
        flash('success', '文件已建立。');
        redirect('/certificates/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('certificates.view');

        $this->render('certificates.show', [
            'title' => '聘書／感謝狀',
            'section' => '業務推動',
            'active' => 'certificates',
            'certificate' => $this->findCertificate((int) $id),
            'canManage' => Permission::can('certificates.manage'),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('certificates.manage');

        $this->render('certificates.form', [
            'title' => '編輯聘書／感謝狀',
            'section' => '業務推動',
            'active' => 'certificates',
            'certificate' => $this->findCertificate((int) $id),
            'templates' => CertificateTemplates::all(),
            'frameStyleOptions' => CertificateFrameStyles::options(),
            'action' => '/certificates/' . $id,
            'preserveBody' => true,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('certificates.manage');
        $this->findCertificate((int) $id);
        $this->validateCertificate('/certificates/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE certificates SET
                template_key = :template_key, variant_key = :variant_key, title = :title,
                frame_style = :frame_style, recipient_name = :recipient_name, body_text = :body_text, term_no = :term_no,
                term_start = :term_start, term_end = :term_end, issued_on = :issued_on,
                signer_title = :signer_title, signer_name = :signer_name, notes = :notes,
                updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'certificates', 'certificates', (int) $id);
        flash('success', '文件已更新。');
        redirect('/certificates/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('certificates.manage');
        $certificate = $this->findCertificate((int) $id);

        Database::pdo()->prepare('DELETE FROM certificates WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'certificates', 'certificates', (int) $id, ['recipient_name' => $certificate['recipient_name']]);
        flash('success', '文件已刪除。');
        redirect('/certificates');
    }

    // ---- 內部輔助 ----

    private function validateCertificate(string $path): void
    {
        if ($error = Validator::required($_POST, ['recipient_name' => '姓名／受贈者', 'body_text' => '正文'])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $templateKey = (string) ($_POST['template_key'] ?? '');
        if (!CertificateTemplates::exists($templateKey)) {
            $this->backWithInput($path, $_POST, '請選擇有效的範本類型。');
        }
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        $templateKey = (string) $_POST['template_key'];
        $tpl = CertificateTemplates::get($templateKey);
        $variantKey = (string) ($_POST['variant_key'] ?? 'standard');
        if (!CertificateTemplates::variantExists($templateKey, $variantKey)) {
            $variantKey = 'standard';
        }

        $termNo = trim((string) ($_POST['term_no'] ?? ''));
        $profile = foundation_profile();

        return [
            'template_key' => $templateKey,
            'variant_key' => $variantKey,
            'title' => $tpl['title'],
            'frame_style' => CertificateFrameStyles::normalize((string) ($_POST['frame_style'] ?? '')),
            'recipient_name' => trim((string) $_POST['recipient_name']),
            'body_text' => trim($this->normalizeNewlines((string) $_POST['body_text'])),
            'term_no' => ($termNo !== '' && ctype_digit($termNo)) ? (int) $termNo : null,
            'term_start' => $this->nullableDate('term_start'),
            'term_end' => $this->nullableDate('term_end'),
            'issued_on' => $this->nullableDate('issued_on') ?? date('Y-m-d'),
            'signer_title' => trim((string) ($_POST['signer_title'] ?? '')) ?: '董事長',
            'signer_name' => trim((string) ($_POST['signer_name'] ?? '')) ?: (string) ($profile['representative'] ?? ''),
            'notes' => $this->nullablePost('notes'),
        ];
    }

    private function nullablePost(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    /**
     * 瀏覽器提交表單時會將 textarea 內容的換行統一轉為 CRLF(\r\n),
     * 導致正文中範本原本以 "\n\n" 分段的段落(受聘人姓名、聘期等)
     * 無法被辨識而合併成一段。於存檔前統一轉回 "\n",確保分段正確。
     */
    private function normalizeNewlines(string $value): string
    {
        return str_replace(["\r\n", "\r"], "\n", $value);
    }

    private function nullableDate(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        if ($value === '') {
            return null;
        }
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    /** @return array<string, mixed> */
    private function blankCertificate(): array
    {
        $profile = foundation_profile();

        return [
            'template_key' => 'board_director',
            'variant_key' => 'standard',
            'frame_style' => 'classic',
            'recipient_name' => '',
            'body_text' => CertificateTemplates::mergeBody('board_director', 'standard', '', null, null, null),
            'term_no' => '',
            'term_start' => '',
            'term_end' => '',
            'issued_on' => date('Y-m-d'),
            'signer_title' => '董事長',
            'signer_name' => (string) ($profile['representative'] ?? ''),
            'notes' => '',
        ];
    }

    /** @return array<string, mixed> */
    private function findCertificate(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT certificates.*, users.name AS created_by_name
             FROM certificates
             LEFT JOIN users ON users.id = certificates.created_by
             WHERE certificates.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$certificate) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到文件']);
            exit;
        }

        return $certificate;
    }
}
