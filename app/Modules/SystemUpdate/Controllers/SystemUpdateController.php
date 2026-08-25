<?php

namespace App\Modules\SystemUpdate\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Modules\SystemUpdate\Services\GithubReleaseService;
use App\Modules\SystemUpdate\Services\MigrationService;
use App\Modules\SystemUpdate\Services\UpdateService;
use App\Modules\SystemUpdate\Services\UpdateLogService;
use Throwable;

final class SystemUpdateController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('system_updates.manage');

        $this->render('system-update.index', [
            'title' => '系統更新',
            'section' => '系統設定',
            'active' => 'system-update',
            'release' => null,
            'repo' => config('app.github_repo', ''),
            'version' => config('app.version', '0.1.0'),
            'logs' => (new UpdateLogService())->latest(),
            'latestPackage' => (new UpdateLogService())->latestSuccessfulDownload(),
        ]);
    }

    public function database(): void
    {
        $this->requirePermission('system_updates.manage');

        $status = ['applied' => [], 'pending' => [], 'total' => 0, 'appliedCount' => 0];
        $error = null;
        try {
            $status = (new MigrationService())->status();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $this->render('system-update.database', [
            'title' => '資料庫更新與檢查',
            'section' => '系統設定',
            'active' => 'system-update-db',
            'version' => config('app.version', '0.1.0'),
            'status' => $status,
            'error' => $error,
        ]);
    }

    public function migrate(): void
    {
        $this->requirePermission('system_updates.manage');

        try {
            $executed = (new MigrationService())->applyPending();
            (new UpdateLogService())->create('migrate', 'success', [
                'message' => $executed ? '套用 ' . count($executed) . ' 個 migration' : '沒有待套用的 migration',
            ]);
            AuditLog::write('migrate', 'system_update', 'schema_migrations', null, ['executed' => $executed]);
            flash('success', $executed
                ? '已套用 ' . count($executed) . ' 個資料庫更新：' . implode('、', $executed)
                : '資料庫已是最新，沒有待套用的更新。');
        } catch (Throwable $e) {
            (new UpdateLogService())->create('migrate', 'failed', ['message' => $e->getMessage()]);
            flash('error', '資料庫更新失敗：' . $e->getMessage());
        }

        redirect('/system-update/database');
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
            'section' => '系統設定',
            'active' => 'system-update',
            'release' => $release,
            'repo' => config('app.github_repo', ''),
            'version' => config('app.version', '0.1.0'),
            'logs' => (new UpdateLogService())->latest(),
            'latestPackage' => (new UpdateLogService())->latestSuccessfulDownload(),
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

    public function apply(): void
    {
        $this->requirePermission('system_updates.manage');

        $logService = new UpdateLogService();

        try {
            $result = (new UpdateService())->applyLatestPackage();

            $logService->create('apply', 'success', [
                'version_to' => $result['version_to'],
                'package_path' => $result['package_path'],
                'package_sha256' => $result['package_sha256'],
                'message' => '更新已套用；程式備份：' . $result['backup_app']
                    . '；資料庫備份：' . $result['backup_database']
                    . '；migration：' . (count($result['migrations']) ? implode(', ', $result['migrations']) : '無'),
            ]);

            AuditLog::write('apply_update', 'system_update', null, null, [
                'version_to' => $result['version_to'],
                'backup_app' => $result['backup_app'],
                'backup_database' => $result['backup_database'],
                'migrations' => $result['migrations'],
            ]);

            flash('success', '更新已套用完成，版本：' . $result['version_to']);
        } catch (Throwable $e) {
            $logService->create('apply', 'failed', ['message' => $e->getMessage()]);
            flash('error', $e->getMessage());
        }

        redirect('/system-update');
    }
}
