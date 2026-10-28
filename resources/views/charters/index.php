<?php
$active = 'charters';
$charters = $charters ?? [];
$documentTitle = '基金會章程';
ob_start();
?>
<div class="print-only" style="text-align:center;margin-bottom:8px">
    <strong style="font-size:16px"><?= e(foundation_name()) ?> — 基金會章程</strong>
</div>

<?php if ($current): ?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">現行章程</p>
            <h2><?= e($current['title']) ?></h2>
            <p class="muted-text">
                訂定日期：<?= e(roc_date($current['enacted_on'])) ?>
                ／核備機關：<?= e($current['approval_authority'] ?: '-') ?>
                ／核准文號：<?= e($current['approval_doc_no'] ?: '-') ?>
                ／核准日期：<?= e(roc_date($current['approval_date'])) ?>
            </p>
        </div>
        <div class="actions no-print">
            <a class="btn primary" href="/charters/<?= e((string) $current['id']) ?>">檢視現行章程</a>
            <?php if (!empty($current['stored_path'])): ?>
                <a class="btn" href="/charters/<?= e((string) $current['id']) ?>/file">下載核備章程檔案</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>章程名冊</h2>
            <p class="muted-text">保存基金會章程的歷次修訂紀錄，並標示現行章程（含經主管機關教育局核備之文號／日期）。</p>
        </div>
        <div class="actions no-print">
            <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage)): ?>
                <a class="btn primary" href="/charters/create">新增章程版本</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="search no-print" method="get" action="/charters">
        <select name="status">
            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>全部</option>
            <option value="current" <?= $status === 'current' ? 'selected' : '' ?>>現行</option>
            <option value="historical" <?= $status === 'historical' ? 'selected' : '' ?>>歷史</option>
        </select>
        <button class="btn" type="submit">查詢</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>狀態</th>
                <th>版次</th>
                <th>章程名稱</th>
                <th>訂定日期</th>
                <th>核備機關</th>
                <th>核准文號</th>
                <th>核准日期</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($charters as $c): ?>
                <tr>
                    <td><span class="badge <?= $c['status'] === 'current' ? 'ok' : 'muted' ?>"><?= e($c['status'] === 'current' ? '現行' : '歷史') ?></span></td>
                    <td><?= e($c['revision_no'] !== null ? '第 ' . $c['revision_no'] . ' 版' : '-') ?></td>
                    <td><?= e($c['title']) ?></td>
                    <td><?= e(roc_date($c['enacted_on'])) ?></td>
                    <td><?= e($c['approval_authority'] ?: '-') ?></td>
                    <td><?= e($c['approval_doc_no'] ?: '-') ?></td>
                    <td><?= e(roc_date($c['approval_date'])) ?></td>
                    <td class="actions no-print">
                        <a class="btn small" href="/charters/<?= e((string) $c['id']) ?>">檢視</a>
                        <?php if (!empty($canManage)): ?>
                            <a class="btn small" href="/charters/<?= e((string) $c['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$charters): ?>
                <tr><td colspan="8" class="empty">尚無章程資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="muted-text no-print" style="margin-top:8px">共 <?= e((string) count($charters)) ?> 筆。</p>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
