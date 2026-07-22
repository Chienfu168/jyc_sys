<?php

namespace App\Modules\SystemUpdate\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Modules\SystemUpdate\Services\GithubReleaseService;
use App\Modules\SystemUpdate\Services\UpdateLogService;
use Throwable;

final class SystemUpdateController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('system_updates.manage');

        $this->render('system-update.index', [
            'title' => '系統更新',
            'active' => 'system-update',
            'release' => null,
            'repo' => config('app.github_repo', ''),
            'version' => config('app.version', '0.1.0'),
            'logs' => (new UpdateLogService())->latest(),
        ]);
    }

    public function check(): void
    {
        $this->requirePermission('system_updates.manage');

        $release = null;
        try {
            $release = (new GithubReleaseService())->latestRelease();
            (new UpdateLogService())->create('check', 'success', [
                'version_to' => $release['tag_name'],
                'message' => '檢查 GitHub release 成功',
            ]);
            AuditLog::write('check_update', 'system_update');
        } catch (Throwable $e) {
            (new UpdateLogService())->create('check', 'failed', ['message' => $e->getMessage()]);
            flash('error', $e->getMessage());
        }

        $this->render('system-update.index', [
            'title' => '系統更新',
            'active' => 'system-update',
            'release' => $release,
            'repo' => config('app.github_repo', ''),
            'version' => config('app.version', '0.1.0'),
            'logs' => (new UpdateLogService())->latest(),
        ]);
    }

    public function download(): void
    {
        $this->requirePermission('system_updates.manage');

        try {
            $github = new GithubReleaseService();
            $release = $github->latestRelease();

            if (!$release['is_newer']) {
                flash('error', '目前版本已是最新或 GitHub release 版本未高於目前版本');
                redirect('/system-update');
            }

            $package = $github->downloadZip($release['zipball_url'], $release['tag_name']);

            (new UpdateLogService())->create('download', 'success', [
                'version_to' => $release['tag_name'],
                'package_path' => 'storage/updates/' . $package['file_name'],
                'package_sha256' => $package['sha256'],
                'message' => '更新包已下載，尚未覆蓋正式程式',
            ]);
            AuditLog::write('download_update_package', 'system_update', null, null, [
                'version_to' => $release['tag_name'],
                'sha256' => $package['sha256'],
            ]);

            flash('success', '更新包已下載：' . $package['file_name']);
        } catch (Throwable $e) {
            (new UpdateLogService())->create('download', 'failed', ['message' => $e->getMessage()]);
            flash('error', $e->getMessage());
        }

        redirect('/system-update');
    }
}
