<?php

namespace App\Modules\OfficialLetters\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\OfficialLetters\LetterFormat;
use PDO;

/**
 * 陳報主管機關公文(函):參考新北市教育局提供的基金會函範例格式,
 * 用於陳報年度工作計畫、經費預算表、董事會議紀錄等文件予主管機關,並可列印用印。
 */
final class OfficialLetterController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('official_letters.view');

        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }

        $stmt = Database::pdo()->prepare(
            'SELECT * FROM official_letters WHERE fiscal_year = :year ORDER BY letter_date DESC, id DESC'
        );
        $stmt->execute(['year' => $year]);

        $this->render('official-letters.index', [
            'title' => '陳報公文',
            'section' => '會計與帳務',
            'active' => 'official-letters',
            'year' => $year,
            'letters' => $stmt->fetchAll(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('official_letters.manage');

        $this->render('official-letters.form', [
            'title' => '新增陳報公文',
            'section' => '會計與帳務',
            'active' => 'official-letters',
            'letter' => $this->blankLetter(),
            'documents' => $this->availableDocuments(),
            'action' => '/official-letters',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('official_letters.manage');
        $this->validateLetter('/official-letters/create');

        Database::pdo()->prepare(
            'INSERT INTO official_letters
             (fiscal_year, letter_number, letter_date, recipient, urgency, confidentiality, attachment_note, subject, basis_lines, attachment_intro, attachment_items, main_copy, cc_copy, signer_title, signer_name, status, created_by, created_at, updated_at)
             VALUES
             (:fiscal_year, :letter_number, :letter_date, :recipient, :urgency, :confidentiality, :attachment_note, :subject, :basis_lines, :attachment_intro, :attachment_items, :main_copy, :cc_copy, :signer_title, :signer_name, :status, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'official_letters', 'official_letters', $id);
        flash('success', '陳報公文已建立。');
        redirect('/official-letters/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('official_letters.view');
        $letter = $this->findLetter((int) $id);

        $this->render('official-letters.show', [
            'title' => '陳報公文',
            'section' => '會計與帳務',
            'active' => 'official-letters',
            'letter' => $letter,
            'basisLines' => LetterFormat::lines($letter['basis_lines']),
            'attachmentItems' => LetterFormat::lines($letter['attachment_items']),
            'mainCopyLines' => LetterFormat::lines($letter['main_copy'] ?? ''),
            'ccCopyLines' => LetterFormat::lines($letter['cc_copy'] ?? ''),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('official_letters.manage');
        $letter = $this->findLetter((int) $id);

        $this->render('official-letters.form', [
            'title' => '編輯陳報公文',
            'section' => '會計與帳務',
            'active' => 'official-letters',
            'letter' => $letter,
            'documents' => $this->availableDocuments(),
            'action' => '/official-letters/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('official_letters.manage');
        $letter = $this->findLetter((int) $id);
        $this->validateLetter('/official-letters/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE official_letters
             SET fiscal_year = :fiscal_year,
                 letter_number = :letter_number,
                 letter_date = :letter_date,
                 recipient = :recipient,
                 urgency = :urgency,
                 confidentiality = :confidentiality,
                 attachment_note = :attachment_note,
                 subject = :subject,
                 basis_lines = :basis_lines,
                 attachment_intro = :attachment_intro,
                 attachment_items = :attachment_items,
                 main_copy = :main_copy,
                 cc_copy = :cc_copy,
                 signer_title = :signer_title,
                 signer_name = :signer_name,
                 status = :status,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => $letter['id'],
        ]);

        AuditLog::write('update', 'official_letters', 'official_letters', (int) $id);
        flash('success', '陳報公文已更新。');
        redirect('/official-letters/' . $id);
    }

    public function issue(string $id): void
    {
        $this->requirePermission('official_letters.manage');
        $letter = $this->findLetter((int) $id);

        if (trim((string) $letter['letter_number']) === '') {
            flash('error', '發文前請先填寫發文字號。');
            redirect('/official-letters/' . $id . '/edit');
        }

        Database::pdo()->prepare('UPDATE official_letters SET status = "issued", updated_at = :updated_at WHERE id = :id')
            ->execute(['updated_at' => now(), 'id' => (int) $id]);

        AuditLog::write('issue', 'official_letters', 'official_letters', (int) $id);
        flash('success', '陳報公文已標記為已發文。');
        redirect('/official-letters/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('official_letters.manage');
        $letter = $this->findLetter((int) $id);

        Database::pdo()->prepare('DELETE FROM official_letters WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'official_letters', 'official_letters', (int) $id, [
            'subject' => $letter['subject'],
        ]);
        flash('success', '陳報公文已刪除。');
        redirect('/official-letters');
    }

    private function validateLetter(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'fiscal_year' => '民國年度',
            'letter_date' => '發文日期',
            'recipient' => '受文者',
            'subject' => '主旨',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $year = normalize_fiscal_year($_POST['fiscal_year']);
        if ($year < 1912 || $year > 2100) {
            $this->backWithInput($path, $_POST, '年度格式不正確。');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['letter_date'] ?? ''))) {
            $this->backWithInput($path, $_POST, '發文日期格式不正確。');
        }
    }

    private function payload(): array
    {
        return [
            'fiscal_year' => normalize_fiscal_year($_POST['fiscal_year']),
            'letter_number' => $this->nullableText('letter_number'),
            'letter_date' => (string) $_POST['letter_date'],
            'recipient' => trim((string) $_POST['recipient']),
            'urgency' => trim((string) ($_POST['urgency'] ?? '')) ?: '普通件',
            'confidentiality' => $this->nullableText('confidentiality'),
            'attachment_note' => $this->nullableText('attachment_note'),
            'subject' => trim((string) $_POST['subject']),
            'basis_lines' => trim((string) ($_POST['basis_lines'] ?? '')),
            'attachment_intro' => $this->nullableText('attachment_intro'),
            'attachment_items' => trim((string) ($_POST['attachment_items'] ?? '')),
            'main_copy' => trim((string) ($_POST['main_copy'] ?? '')),
            'cc_copy' => trim((string) ($_POST['cc_copy'] ?? '')),
            'signer_title' => trim((string) ($_POST['signer_title'] ?? '')) ?: '董事長',
            'signer_name' => $this->nullableText('signer_name'),
            'status' => in_array($_POST['status'] ?? '', ['draft', 'issued'], true) ? (string) $_POST['status'] : 'draft',
        ];
    }

    private function findLetter(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM official_letters WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $letter = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$letter) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到陳報公文']);
            exit;
        }

        return $letter;
    }

    /**
     * 供表單快速帶入附件清單:列出系統既有的工作計畫、經費預算表與董事會議紀錄,
     * 依年度(民國)分組標示,方便使用者勾選帶入附件文字。
     *
     * @return array<int, array{group: string, label: string, year: ?int}>
     */
    private function availableDocuments(): array
    {
        $documents = [];
        $pdo = Database::pdo();

        try {
            $plans = $pdo->query(
                'SELECT fiscal_year, title FROM work_plans ORDER BY fiscal_year DESC, id DESC LIMIT 30'
            )->fetchAll();
            foreach ($plans as $plan) {
                $documents[] = [
                    'group' => '工作計畫',
                    'label' => roc_year((int) $plan['fiscal_year']) . '年度' . (string) $plan['title'],
                    'year' => (int) $plan['fiscal_year'],
                ];
            }
        } catch (\Throwable) {
            // 資料表尚未建立時略過。
        }

        try {
            $budgets = $pdo->query(
                'SELECT fiscal_year, title FROM annual_budgets ORDER BY fiscal_year DESC, id DESC LIMIT 30'
            )->fetchAll();
            foreach ($budgets as $budget) {
                $documents[] = [
                    'group' => '經費預算表',
                    'label' => roc_year((int) $budget['fiscal_year']) . '年度' . (string) $budget['title'],
                    'year' => (int) $budget['fiscal_year'],
                ];
            }
        } catch (\Throwable) {
            // 略過。
        }

        try {
            $meetings = $pdo->query(
                'SELECT term_no, session_no, meeting_date FROM board_meetings ORDER BY meeting_date DESC, id DESC LIMIT 30'
            )->fetchAll();
            foreach ($meetings as $meeting) {
                $year = (int) substr((string) $meeting['meeting_date'], 0, 4);
                $documents[] = [
                    'group' => '董事會議紀錄',
                    'label' => \App\Domain\BoardMeetings\MeetingLabel::sessionTitle(
                        (int) $meeting['term_no'],
                        (int) $meeting['session_no']
                    ) . '紀錄（含簽到表）',
                    'year' => $year > 0 ? $year : null,
                ];
            }
        } catch (\Throwable) {
            // 略過。
        }

        return $documents;
    }

    private function blankLetter(): array
    {
        $year = (int) date('Y');
        $profile = foundation_profile();

        return [
            'fiscal_year' => $year,
            'letter_number' => '',
            'letter_date' => date('Y-m-d'),
            'recipient' => $profile['competent_authority'] ?: '',
            'urgency' => '普通件',
            'confidentiality' => '',
            'attachment_note' => '如說明三',
            'subject' => sprintf('有關陳報本會 %d 年度工作計畫、經費預算及董事會議紀錄一案，請鑒核。', roc_year($year)),
            'basis_lines' => "依據「財團法人法」辦理。\n本會" . roc_year($year) . '年度工作計畫、經費預算等業經本會董事會議審定通過。',
            'attachment_intro' => '檢附左列文件各 1 份：',
            'attachment_items' => roc_year($year) . "年度工作計畫。\n經費預算表。\n董事會議紀錄（含簽到表）。\n核定捐助章程。\n風險評估報告(工作計畫及經費預算與洗錢或資恐高風險國家或地區有關者，並應檢附風險評估報告。)",
            'main_copy' => $profile['competent_authority'] ?: '',
            'cc_copy' => '本會存查。',
            'signer_title' => '董事長',
            'signer_name' => $profile['representative'] ?: '',
            'status' => 'draft',
        ];
    }

    private function nullableText(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }
}
