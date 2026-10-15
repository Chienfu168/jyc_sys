<?php
$active = 'schools';
$school = $school ?? [];
$currentStaff = $currentStaff ?? [];
$formerStaff = $formerStaff ?? [];
$activities = $activities ?? [];
$titles = $titles ?? [];
$documentTitle = '學校資料';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($school['name']) ?>
                <span class="badge <?= $school['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($school['status'] === 'active' ? '啟用' : '已封存') ?></span>
            </h2>
            <p class="muted-text"><?= e($school['category'] ?: '學校通訊錄') ?></p>
        </div>
        <div class="actions no-print">
            <a class="btn" href="/schools">返回學校通訊錄</a>
            <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage)): ?>
                <a class="btn" href="/schools/<?= e((string) $school['id']) ?>/edit">編輯</a>
                <form method="post" action="/schools/<?= e((string) $school['id']) ?>/toggle">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit"><?= $school['status'] === 'active' ? '封存' : '恢復啟用' ?></button>
                </form>
                <form method="post" action="/schools/<?= e((string) $school['id']) ?>/delete" onsubmit="return confirm('確定要刪除此學校？將一併刪除其人員紀錄，此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>學校名稱</th><td><?= e($school['name']) ?></td>
            <th>類別</th><td><?= e($school['category'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>地區類別</th><td><?= e($school['area_type'] ?: '-') ?></td>
            <th>電話</th><td><?= e($school['phone'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>傳真</th><td colspan="3"><?= e($school['fax'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>地址</th><td colspan="3"><?= e($school['address'] ?: '-') ?></td>
        </tr>
        <?php if (!empty($school['notes'])): ?>
        <tr>
            <th>備註</th><td colspan="3"><?= nl2br(e($school['notes'])) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="no-print">
            <th>建立人</th><td><?= e($school['created_by_name'] ?? '-') ?></td>
            <th>更新時間</th><td><?= e($school['updated_at'] ? substr((string) $school['updated_at'], 0, 16) : '-') ?></td>
        </tr>
        </tbody>
    </table>

    <section class="panel subtle" style="margin-top:18px;">
        <div class="panel-header">
            <div>
                <h3 style="margin:0;">現任人員</h3>
                <p class="muted-text">校長、主任等現任人員，職稱可自行輸入(如教務主任、總務主任)。</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>職稱</th>
                    <th>姓名</th>
                    <th>聯絡方式</th>
                    <th>到任日期</th>
                    <th class="actions no-print">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($currentStaff as $person): ?>
                    <tr>
                        <td><strong><?= e($person['title']) ?></strong></td>
                        <td><?= e($person['name']) ?></td>
                        <td>
                            <?= e($person['phone'] ?: ($person['mobile'] ?: '-')) ?>
                            <?php if (!empty($person['email'])): ?>
                                <div class="muted-text"><?= e($person['email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e(roc_date($person['started_on'])) ?></td>
                        <td class="actions no-print">
                            <?php if (!empty($canManage)): ?>
                                <a class="btn small" href="/schools/<?= e((string) $school['id']) ?>/staff/<?= e((string) $person['id']) ?>/edit">編輯</a>
                                <form method="post" action="/schools/<?= e((string) $school['id']) ?>/staff/<?= e((string) $person['id']) ?>/toggle" onsubmit="return confirm('確定將此人員標示為卸任(前任)？');">
                                    <?= csrf_field() ?>
                                    <button class="btn small" type="submit">標示卸任</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$currentStaff): ?>
                    <tr><td colspan="5" class="empty">尚無現任人員資料。</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($formerStaff): ?>
    <section class="panel subtle" style="margin-top:18px;">
        <div class="panel-header">
            <div>
                <h3 style="margin:0;">歷任(前任)人員</h3>
                <p class="muted-text">人員更替時標示為卸任，保留任期歷史紀錄。</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>職稱</th>
                    <th>姓名</th>
                    <th>任期</th>
                    <th class="actions no-print">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($formerStaff as $person): ?>
                    <tr>
                        <td><?= e($person['title']) ?></td>
                        <td><?= e($person['name']) ?></td>
                        <td><?= e(roc_date($person['started_on'])) ?> ～ <?= e(roc_date($person['ended_on'])) ?></td>
                        <td class="actions no-print">
                            <?php if (!empty($canManage)): ?>
                                <a class="btn small" href="/schools/<?= e((string) $school['id']) ?>/staff/<?= e((string) $person['id']) ?>/edit">編輯</a>
                                <form method="post" action="/schools/<?= e((string) $school['id']) ?>/staff/<?= e((string) $person['id']) ?>/toggle">
                                    <?= csrf_field() ?>
                                    <button class="btn small" type="submit">恢復現任</button>
                                </form>
                                <form method="post" action="/schools/<?= e((string) $school['id']) ?>/staff/<?= e((string) $person['id']) ?>/delete" onsubmit="return confirm('確定要刪除此人員紀錄？此操作無法復原。');">
                                    <?= csrf_field() ?>
                                    <button class="btn small" type="submit">刪除</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($canManage)): ?>
        <section class="panel subtle no-print" style="margin-top:18px;">
            <div class="panel-header">
                <div>
                    <h3 style="margin:0;">新增校長／主任等人員</h3>
                </div>
            </div>
            <div class="feature-card" style="border:0;padding:0;">
                <form class="form grid-form compact-form" method="post" action="/schools/<?= e((string) $school['id']) ?>/staff">
                    <?= csrf_field() ?>
                    <label>
                        <span>職稱</span>
                        <input type="text" name="title" list="schoolStaffTitles" required value="<?= e((string) old('title')) ?>">
                        <datalist id="schoolStaffTitles">
                            <?php foreach ($titles as $t): ?>
                                <option value="<?= e($t) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </label>
                    <label>
                        <span>姓名</span>
                        <input type="text" name="name" required value="<?= e((string) old('name')) ?>">
                    </label>
                    <label>
                        <span>電話</span>
                        <input type="text" name="phone" value="<?= e((string) old('phone')) ?>">
                    </label>
                    <label>
                        <span>手機</span>
                        <input type="text" name="mobile" value="<?= e((string) old('mobile')) ?>">
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?= e((string) old('email')) ?>">
                    </label>
                    <label>
                        <span>到任日期</span>
                        <input type="date" name="started_on" value="<?= e((string) old('started_on')) ?>">
                    </label>
                    <label>
                        <span>狀態</span>
                        <?php $newStaffStatus = old('status', 'current'); ?>
                        <select name="status">
                            <option value="current" <?= $newStaffStatus === 'current' ? 'selected' : '' ?>>現任</option>
                            <option value="former" <?= $newStaffStatus === 'former' ? 'selected' : '' ?>>前任(補登歷史紀錄)</option>
                        </select>
                    </label>
                    <label>
                        <span>卸任日期<small class="field-hint">（選填前任時適用）</small></span>
                        <input type="date" name="ended_on" value="<?= e((string) old('ended_on')) ?>">
                    </label>
                    <label class="span-2">
                        <span>備註</span>
                        <input type="text" name="notes" value="<?= e((string) old('notes')) ?>">
                    </label>
                    <div class="form-actions span-2">
                        <button class="btn primary" type="submit">新增人員</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel subtle" style="margin-top:18px;">
        <div class="panel-header">
            <div>
                <h3 style="margin:0;">相關活動</h3>
                <p class="muted-text">活動主要依附學校辦理，此處彙整該校相關活動紀錄(最多顯示 50 筆)。</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>時間</th>
                    <th>活動名稱</th>
                    <th>狀態</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($activities as $a): ?>
                    <tr>
                        <td><?= e(roc_date(substr((string) $a['starts_at'], 0, 10))) ?></td>
                        <td><a class="text-link" href="/activities/<?= e((string) $a['id']) ?>"><?= e($a['title']) ?></a></td>
                        <td><?= e(['draft' => '草稿', 'published' => '已發布', 'closed' => '結案', 'cancelled' => '取消'][$a['status']] ?? $a['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$activities): ?>
                    <tr><td colspan="3" class="empty">尚無相關活動紀錄。</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
