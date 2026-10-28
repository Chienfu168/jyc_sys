<?php
$active = 'charters';
$charter = $charter ?? [];
$documentTitle = '章程詳情';
ob_start();
?>
<div class="print-only" style="text-align:center;margin-bottom:8px">
    <strong style="font-size:16px"><?= e(foundation_name()) ?> — <?= e($charter['title']) ?></strong>
</div>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($charter['title']) ?>
                <span class="badge <?= $charter['status'] === 'current' ? 'ok' : 'muted' ?>"><?= e($charter['status'] === 'current' ? '現行' : '歷史') ?></span>
            </h2>
            <p class="muted-text"><?= e($charter['revision_no'] !== null ? '第 ' . $charter['revision_no'] . ' 版' : '章程名冊') ?></p>
        </div>
        <div class="actions no-print">
            <a class="btn" href="/charters">返回章程名冊</a>
            <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($charter['stored_path'])): ?>
                <a class="btn" href="/charters/<?= e((string) $charter['id']) ?>/file">下載檔案</a>
            <?php endif; ?>
            <?php if (!empty($canManage)): ?>
                <a class="btn" href="/charters/<?= e((string) $charter['id']) ?>/edit">編輯</a>
                <?php if ($charter['status'] !== 'current'): ?>
                    <form method="post" action="/charters/<?= e((string) $charter['id']) ?>/set-current" onsubmit="return confirm('確定要將此版本設為現行章程？原現行版本將改為歷史。');">
                        <?= csrf_field() ?>
                        <button class="btn" type="submit">設為現行</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="/charters/<?= e((string) $charter['id']) ?>/delete" onsubmit="return confirm('確定要刪除此章程版本？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>修訂版次</th><td><?= e($charter['revision_no'] !== null ? '第 ' . $charter['revision_no'] . ' 版' : '-') ?></td>
            <th>訂定／異動日期</th><td><?= e(roc_date($charter['enacted_on'])) ?></td>
        </tr>
        <tr>
            <th>核備機關</th><td><?= e($charter['approval_authority'] ?: '-') ?></td>
            <th>核准文號</th><td><?= e($charter['approval_doc_no'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>核准日期</th><td colspan="3"><?= e(roc_date($charter['approval_date'])) ?></td>
        </tr>
        <?php if (!empty($charter['amendment_summary'])): ?>
        <tr>
            <th>修訂重點說明</th><td colspan="3"><?= nl2br(e($charter['amendment_summary'])) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($charter['notes'])): ?>
        <tr>
            <th>備註</th><td colspan="3"><?= nl2br(e($charter['notes'])) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="no-print">
            <th>建立人</th><td><?= e($charter['created_by_name'] ?? '-') ?></td>
            <th>更新時間</th><td><?= e($charter['updated_at'] ? substr((string) $charter['updated_at'], 0, 16) : '-') ?></td>
        </tr>
        </tbody>
    </table>

    <?php if (!empty($charter['content'])): ?>
        <section class="panel subtle" style="margin-top:16px">
            <div class="panel-header no-print"><h3 style="margin:0;">章程全文</h3></div>
            <div class="print-notes" style="white-space:pre-wrap;"><?= nl2br(e($charter['content'])) ?></div>
        </section>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
