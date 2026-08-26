<?php

use App\Core\Auth;
use App\Core\Csrf;

function base_path(string $path = ''): string
{
    $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function app_path(string $path = ''): string
{
    return base_path('app' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }

    return $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = env($key, $default);
    if (is_bool($value)) {
        return $value;
    }

    return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
}

function config(string $key, mixed $default = null): mixed
{
    static $config = [];

    [$file, $item] = array_pad(explode('.', $key, 2), 2, null);

    if (!isset($config[$file])) {
        $path = base_path('config' . DIRECTORY_SEPARATOR . $file . '.php');
        $config[$file] = file_exists($path) ? require $path : [];
    }

    return $item === null ? $config[$file] : ($config[$file][$item] ?? $default);
}

function asset_url(string $path): string
{
    $assetPath = '/' . ltrim($path, '/');
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    if ($scriptDir === '/public' || str_ends_with($scriptDir, '/public')) {
        $scriptDir = substr($scriptDir, 0, -7);
    }

    if ($scriptDir === '' || $scriptDir === '.' || $scriptDir === '/') {
        return $assetPath;
    }

    return $scriptDir . $assetPath;
}

function asset_version(): string
{
    return preg_replace('/^v/i', '', (string) config('app.version', '0'));
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewPath = base_path('resources/views/' . str_replace('.', '/', $template) . '.php');

    if (!file_exists($viewPath)) {
        throw new RuntimeException("View not found: {$template}");
    }

    require $viewPath;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function auth(): Auth
{
    return Auth::instance();
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function roc_year(int|string|null $year = null): int
{
    $year = (int) ($year ?: date('Y'));
    return $year > 1911 ? $year - 1911 : $year;
}

function gregorian_year_from_roc(int|string|null $year = null): int
{
    $year = (int) ($year ?: date('Y'));
    return $year < 1912 ? $year + 1911 : $year;
}

function normalize_fiscal_year(int|string|null $year = null): int
{
    return gregorian_year_from_roc($year);
}

function roc_year_label(int|string|null $year = null): string
{
    return '民國 ' . roc_year($year) . ' 年';
}

function roc_date(?string $date): string
{
    if (!$date || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
        return '-';
    }

    return sprintf('民國 %d 年 %d 月 %d 日', ((int) $matches[1]) - 1911, (int) $matches[2], (int) $matches[3]);
}

function roc_date_range(?string $start, ?string $end): string
{
    return roc_date($start) . ' ~ ' . roc_date($end);
}

function foundation_profile(): array
{
    static $profile = null;

    if ($profile !== null) {
        return $profile;
    }

    $fallback = [
        'foundation_name' => config('app.name'),
        'english_name' => '',
        'tax_id' => '',
        'registration_no' => '',
        'competent_authority' => '',
        'approval_date' => '',
        'approval_doc_no' => '',
        'representative' => '',
        'executive_director' => '',
        'undertaker' => '',
        'phone' => '',
        'email' => '',
        'website' => '',
        'logo_path' => '',
        'address' => '',
        'mailing_address' => '',
        'mission' => '',
        'service_area' => '',
        'fiscal_year_start_month' => 1,
    ];

    try {
        $stmt = \App\Core\Database::pdo()->query('SELECT * FROM foundation_profiles WHERE id = 1 LIMIT 1');
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: $fallback;
    } catch (Throwable) {
        $profile = $fallback;
    }

    return $profile + $fallback;
}

function foundation_name(): string
{
    return foundation_profile()['foundation_name'] ?: config('app.name');
}

/**
 * 將設定的基金會 LOGO 讀取為 data: URI(base64),供列印文件與畫面直接內嵌。
 *
 * LOGO 檔案存放於 storage/(非網站可直接存取),以 data URI 內嵌可在瀏覽器
 * 列印/另存 PDF 時穩定呈現,且不需另設路由或驗證。無 LOGO 或檔案不存在時回傳 null。
 */
function foundation_logo_data_uri(): ?string
{
    static $cached = false;
    static $value = null;

    if ($cached) {
        return $value;
    }

    $cached = true;
    $path = trim((string) (foundation_profile()['logo_path'] ?? ''));
    if ($path === '') {
        return $value = null;
    }

    $full = storage_path($path);
    if (!is_file($full) || !is_readable($full)) {
        return $value = null;
    }

    $mimeByExt = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
    ];
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $mime = $mimeByExt[$ext] ?? null;
    if ($mime === null) {
        return $value = null;
    }

    $data = @file_get_contents($full);
    if ($data === false || $data === '') {
        return $value = null;
    }

    return $value = 'data:' . $mime . ';base64,' . base64_encode($data);
}

/** 目前登入者的使用者編號(未登入為 0)。 */
function current_user_id(): int
{
    return (int) (auth()->user()['id'] ?? 0);
}

/**
 * 目前登入者是否為該筆資料的建立者(created_by)。
 * 供畫面判斷是否顯示「編輯／刪除」按鈕:建立者可管理自己建立的資料。
 */
function owns_record(int|string|null $ownerId): bool
{
    $uid = current_user_id();
    return $uid > 0 && (int) $ownerId === $uid;
}

/**
 * 依模組屬性回傳列印文件下方的簽核鏈。
 *
 * 每個模組的核章關係不同:人事類經人事主管、財務類經會計、採購類經總務等,
 * 一律以「承辦／經手 → 單位主管 → 執行長 → 董事長」的層級呈現。
 * 已於基金會基本資料設定者(執行長、董事長、承辦)自動帶入姓名,
 * 其餘職務留白供實體簽章。$context 通常帶入頁面的 $active 模組鍵。
 *
 * @return array<int, array{label: string, name: string}>
 */
function signature_chain(?string $context = null): array
{
    $profile = foundation_profile();
    $exec = (string) ($profile['executive_director'] ?? '');
    $rep = (string) ($profile['representative'] ?? '');
    $undertaker = (string) ($profile['undertaker'] ?? '');

    $finance = [['製表', ''], ['會計', ''], ['執行長', $exec], ['董事長', $rep]];
    $affairs = [['承辦', $undertaker], ['單位主管', ''], ['執行長', $exec], ['董事長', $rep]];

    $chains = [
        // 人事差勤:經人事主管。
        'leave-requests' => [['申請人', ''], ['人事主管', ''], ['執行長', $exec], ['董事長', $rep]],
        'personnel' => [['承辦', $undertaker], ['人事主管', ''], ['執行長', $exec], ['董事長', $rep]],
        'volunteers' => [['承辦', $undertaker], ['人事主管', ''], ['執行長', $exec], ['董事長', $rep]],
        'payroll' => [['製表', ''], ['會計', ''], ['執行長', $exec], ['董事長', $rep]],
        // 採購:經總務。
        'purchase-requests' => [['申請人', ''], ['總務', ''], ['執行長', $exec], ['董事長', $rep]],
        // 支出核銷:經會計。
        'travel-expenses' => [['申請人', ''], ['會計', ''], ['執行長', $exec], ['董事長', $rep]],
        'lecturer-expenses' => [['承辦', $undertaker], ['會計', ''], ['執行長', $exec], ['董事長', $rep]],
        'income-expenses' => $finance,
        'petty-cash' => [['經手人', ''], ['會計', ''], ['執行長', $exec], ['董事長', $rep]],
        // 財務會計與決算報表:經會計。
        'accounting' => $finance,
        'reconciliation' => [['對帳', ''], ['會計', ''], ['執行長', $exec], ['董事長', $rep]],
        'annual-budgets' => [['編製', ''], ['會計', ''], ['執行長', $exec], ['董事長', $rep]],
        'operating-statements' => $finance,
        'balance-sheets' => $finance,
        'cash-flow-statements' => $finance,
        'net-asset-statements' => $finance,
        // 財產:經保管人與會計。
        'foundation-assets' => [['保管人', ''], ['會計', ''], ['執行長', $exec], ['董事長', $rep]],
        // 捐款:經經手人與會計。
        'donations' => [['經手人', ''], ['會計', ''], ['執行長', $exec], ['董事長', $rep]],
        // 業務推動:經單位主管。
        'work-plans' => $affairs,
        'activities' => $affairs,
        'projects' => $affairs,
        'calendar' => $affairs,
        'lecturers' => $affairs,
        'board-meetings' => $affairs,
    ];

    $chain = $chains[$context] ?? [['承辦', $undertaker], ['會計人員', ''], ['執行長', $exec], ['董事長', $rep]];

    return array_map(
        static fn (array $role): array => ['label' => $role[0], 'name' => $role[1]],
        $chain
    );
}
