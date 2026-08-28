<?php

namespace App\Modules\SystemUpdate\Services;

use RuntimeException;

final class GithubReleaseService
{
    public function latestRelease(): array
    {
        $repo = trim((string) config('app.github_repo', ''));
        if ($repo === '' || !preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo)) {
            throw new RuntimeException('尚未設定有效的 GITHUB_REPO，例如 your-org/foundation-system');
        }

        $channel = config('app.update_channel', 'stable');
        $endpoint = $channel === 'stable'
            ? "https://api.github.com/repos/{$repo}/releases/latest"
            : "https://api.github.com/repos/{$repo}/releases";

        $data = $this->requestJson($endpoint);
        $release = $channel === 'stable' ? $data : $this->firstUsableRelease($data);

        if (!$release) {
            throw new RuntimeException('GitHub 找不到可用的 release');
        }

        return [
            'tag_name' => (string) ($release['tag_name'] ?? ''),
            'name' => (string) ($release['name'] ?? $release['tag_name'] ?? ''),
            'published_at' => (string) ($release['published_at'] ?? ''),
            'html_url' => (string) ($release['html_url'] ?? ''),
            'zipball_url' => $this->downloadUrl((string) ($release['tag_name'] ?? '')),
            'body' => (string) ($release['body'] ?? ''),
            'prerelease' => (bool) ($release['prerelease'] ?? false),
            'draft' => (bool) ($release['draft'] ?? false),
            'is_newer' => $this->isNewer((string) ($release['tag_name'] ?? ''), (string) config('app.version', '0.0.0')),
        ];
    }

    public function downloadZip(string $zipballUrl, string $tagName): array
    {
        if ($zipballUrl === '' || !preg_match('#^https://(api\.github\.com|codeload\.github\.com)/#', $zipballUrl)) {
            throw new RuntimeException('GitHub 更新包網址無效');
        }

        $safeTag = preg_replace('/[^A-Za-z0-9_.-]/', '_', $tagName) ?: date('Ymd_His');
        $target = storage_path('updates' . DIRECTORY_SEPARATOR . $safeTag . '.zip');
        $content = $this->request($zipballUrl, true);

        if ($content === '') {
            throw new RuntimeException('下載的更新包是空檔案');
        }

        file_put_contents($target, $content);

        if (!$this->looksLikeZip($target)) {
            @unlink($target);
            throw new RuntimeException('下載內容不是有效的 zip 更新包，請確認 GitHub Release 與下載權限');
        }

        return [
            'path' => $target,
            'file_name' => basename($target),
            'size' => filesize($target) ?: 0,
            'sha256' => hash_file('sha256', $target) ?: '',
        ];
    }

    private function firstUsableRelease(array $releases): ?array
    {
        foreach ($releases as $release) {
            if (!($release['draft'] ?? false)) {
                return $release;
            }
        }

        return null;
    }

    private function requestJson(string $url): array
    {
        $content = $this->request($url, false);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new RuntimeException('GitHub 回傳格式無法解析');
        }

        if (isset($data['message']) && !isset($data['tag_name'])) {
            throw new RuntimeException('GitHub API 錯誤：' . $data['message']);
        }

        return $data;
    }

    private function request(string $url, bool $binary): string
    {
        $headers = [
            'User-Agent: Foundation-System-Updater',
            'Accept: ' . ($binary ? 'application/zip, application/octet-stream, */*' : 'application/vnd.github+json'),
        ];

        if (!$binary) {
            $headers[] = 'X-GitHub-Api-Version: 2022-11-28';
        }

        $token = trim((string) config('app.github_token', ''));
        if ($token !== '' && str_starts_with($url, 'https://api.github.com/')) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        if (function_exists('curl_init')) {
            return $this->requestWithCurl($url, $headers);
        }

        return $this->requestWithStreams($url, $headers);
    }

    private function requestWithCurl(string $url, array $headers): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ]);

        $content = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($content === false || $status >= 400) {
            throw new RuntimeException($error ?: $this->httpErrorMessage($url, $status, is_string($content) ? $content : ''));
        }

        return (string) $content;
    }

    private function requestWithStreams(string $url, array $headers): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);

        $content = file_get_contents($url, false, $context);
        $status = $this->streamStatusCode($http_response_header ?? []);

        if ($content === false || $status >= 400) {
            throw new RuntimeException($this->httpErrorMessage($url, $status, is_string($content) ? $content : ''));
        }

        return (string) $content;
    }

    private function streamStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 200;
    }

    private function httpErrorMessage(string $url, int $status, string $body = ''): string
    {
        if ($status === 404 && str_contains($url, '/releases/latest')) {
            return 'GitHub 找不到 Latest Release。請先到 GitHub 建立正式 Release；如果 repo 是私有，請確認 GITHUB_TOKEN 具備 Contents: Read-only 權限。';
        }

        if ($status === 404 && str_contains($url, '/releases')) {
            return 'GitHub 找不到可用的 Release。請確認 repo 名稱正確、已建立 Release，或 GITHUB_TOKEN 有讀取權限。';
        }

        // 先判斷是否為「請求次數上限」(rate limit),與權限問題分開提示。
        // 公開 repo 免 Token 即可讀取,但未帶 Token 時每小時僅 60 次、且共用主機 IP 易觸發上限。
        $hasToken = trim((string) config('app.github_token', '')) !== '';
        if (($status === 403 || $status === 429) && $this->isRateLimited($body)) {
            $suffix = $hasToken
                ? '請稍後再試。'
                : '此為公開 repo，免 Token 亦可更新；未設定 GITHUB_TOKEN 時每小時上限僅 60 次，且與同主機其他網站共用 IP 額度，容易觸發。可稍後再試，或於 .env 設定 GITHUB_TOKEN（僅需 Contents: Read-only）將額度提高到每小時 5000 次。';
            return 'GitHub API 請求次數已達上限。' . $suffix;
        }

        if ($status === 401) {
            return 'GitHub Token 無效或已過期。請確認 .env 的 GITHUB_TOKEN 正確；公開 repo 若不需私有存取，也可清空 GITHUB_TOKEN 改用免 Token 方式更新。';
        }

        if ($status === 403) {
            return 'GitHub 拒絕存取（權限不足）。若 repo 為私有，請確認 GITHUB_TOKEN 具備 Contents: Read-only 權限；公開 repo 通常免 Token 即可讀取。';
        }

        if ($status === 415) {
            return 'GitHub 不接受目前的下載請求格式。請更新系統更新模組後再下載 Release zip。';
        }

        if ($status === 0) {
            return '無法連線至 GitHub（可能被主機阻擋對外連線或連線逾時），請確認主機允許對外 HTTPS 連線。';
        }

        return "GitHub 請求失敗，HTTP {$status}";
    }

    /** 由回應內容判斷是否為 GitHub 的請求次數上限錯誤。 */
    private function isRateLimited(string $body): bool
    {
        if ($body === '') {
            return true; // 403/429 但無內容時,多為速率限制,採較保守判斷。
        }
        $lower = strtolower($body);
        return str_contains($lower, 'rate limit') || str_contains($lower, 'secondary rate');
    }

    private function downloadUrl(string $tagName): string
    {
        $repo = trim((string) config('app.github_repo', ''));
        if ($repo === '' || $tagName === '') {
            return '';
        }

        return 'https://codeload.github.com/' . $repo . '/zip/refs/tags/' . rawurlencode($tagName);
    }

    private function looksLikeZip(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return false;
        }

        $signature = fread($handle, 4);
        fclose($handle);

        return $signature === "PK\x03\x04" || $signature === "PK\x05\x06" || $signature === "PK\x07\x08";
    }

    private function isNewer(string $remoteTag, string $currentVersion): bool
    {
        $remote = ltrim($remoteTag, 'vV');
        $current = ltrim($currentVersion, 'vV');

        return version_compare($remote, $current, '>');
    }
}
