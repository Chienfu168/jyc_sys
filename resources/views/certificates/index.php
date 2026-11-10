<?php
$active = 'certificates';
$certificates = $certificates ?? [];
$templateOptions = $templateOptions ?? [];
$documentTitle = '聘書與感謝狀';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>聘書與感謝狀</h2>
            <p class="muted-text">依固定範本開立董事聘書、基金會講師聘書、樹林社區大學校長聘書與感謝狀，可查詢歷次開立紀錄並重印、複製再開立。</p>
        </div>
        <div class="actions no-print">
            <?php if (!empty($canManage)): ?>
                <a class="btn primary" href="/certificates/create">開立文件</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="search bank-filter no-print" method="get" action="/certificates">
        <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="姓名、內容關鍵字">
        <select name="template">
            <option value="">全部範本</option>
            <?php foreach ($templateOptions as $opt): ?>
                <option value="<?= e($opt['key']) ?>" <?= $templateKey === $opt['key'] ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">查詢</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>範本</th>
                <th>姓名／受贈者</th>
                <th>開立日期</th>
                <th>落款</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($certificates as $c): ?>
                <tr>
                    <td><?= e($c['title']) ?><span class="muted-text">（<?= e(\App\Domain\Certificates\CertificateTemplates::get($c['template_key'])['label']) ?>）</span></td>
                    <td><strong><?= e($c['recipient_name']) ?></strong></td>
                    <td><?= e(roc_date($c['issued_on'])) ?></td>
                    <td><?= e($c['signer_title'] . ' ' . $c['signer_name']) ?></td>
                    <td class="actions no-print">
                        <a class="btn small" href="/certificates/<?= e((string) $c['id']) ?>">檢視</a>
                        <?php if (!empty($canManage)): ?>
                            <a class="btn small" href="/certificates/<?= e((string) $c['id']) ?>/edit">編輯</a>
                            <a class="btn small" href="/certificates/<?= e((string) $c['id']) ?>/duplicate">複製</a>
                            <form method="post" action="/certificates/<?= e((string) $c['id']) ?>/delete" onsubmit="return confirm('確定要刪除此文件？此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$certificates): ?>
                <tr><td colspan="5" class="empty">尚無開立紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="muted-text no-print" style="margin-top:8px">共 <?= e((string) count($certificates)) ?> 筆。</p>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
