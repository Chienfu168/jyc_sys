<?php
$active = 'access-control';

$blocks = $blocks ?? [];
$offenders = $offenders ?? [];

$reasonLabels = [
    'login_bruteforce' => '登入暴力嘗試',
    'manual' => '手動封鎖',
];

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>自動封鎖累犯 IP（fail2ban 式）</h2>
            <p class="muted-text">
                同一來源 IP 在 <strong><?= e((string) $windowMinutes) ?></strong> 分鐘內登入失敗達
                <strong><?= e((string) $threshold) ?></strong> 次，即自動暫時封鎖
                <strong><?= e((string) $blockMinutes) ?></strong> 分鐘，被封鎖期間該 IP 的所有連線都會被擋下。
                內網位址與「連線來源管制」允許清單一律豁免，不會誤鎖自己人。
            </p>
        </div>
        <div class="actions">
            <a class="btn" href="/access-control">← 連線來源管制</a>
        </div>
    </div>
    <p class="muted-text" style="margin:0">您目前的連線位址：<strong><?= e($currentIp !== '' ? $currentIp : '未知') ?></strong></p>
</section>

<?php if (!empty($killSwitch)): ?>
    <section class="panel" style="border-left:4px solid #9a6a00">
        <p class="muted-text" style="margin:0">
            提醒：目前 <code>.env</code> 設定了 <code>IP_AUTOBLOCK_DISABLED=true</code>（緊急停用開關），
            因此自動封鎖與現有封鎖名單目前都不會生效。排除問題後，請移除該設定使功能恢復。
        </p>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>目前封鎖中的 IP</h2>
            <p class="muted-text">封鎖為暫時性，到期會自動解除。若需要提前放行，請按「解除」。</p>
        </div>
    </div>

    <?php if (!$blocks): ?>
        <p class="muted-text">目前沒有被封鎖的 IP。</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>來源 IP</th>
                        <th>原因</th>
                        <th style="text-align:right">失敗次數</th>
                        <th>封鎖到期</th>
                        <th>備註／操作者</th>
                        <th style="text-align:right">操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($blocks as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['ip_address'] ?? '')) ?></td>
                        <td><?= e($reasonLabels[$row['reason'] ?? ''] ?? (string) ($row['reason'] ?? '')) ?></td>
                        <td style="text-align:right"><?= e((string) ($row['fail_count'] ?? 0)) ?></td>
                        <td><?= e((string) ($row['blocked_until'] ?? '')) ?></td>
                        <td class="muted-text">
                            <?php
                            $meta = [];
                            if (!empty($row['notes'])) {
                                $meta[] = (string) $row['notes'];
                            }
                            if (!empty($row['blocked_by_name'])) {
                                $meta[] = '由 ' . (string) $row['blocked_by_name'];
                            }
                            echo e(implode('；', $meta));
                            ?>
                        </td>
                        <td style="text-align:right">
                            <form method="post" action="/access-control/blocked/unblock" onsubmit="return confirm('確定要解除封鎖此 IP？');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ip" value="<?= e((string) ($row['ip_address'] ?? '')) ?>">
                                <button class="btn" type="submit">解除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>手動封鎖 IP</h2>
            <p class="muted-text">可在觀察到可疑來源時提前封鎖。內網位址與允許清單內的 IP 無法封鎖。</p>
        </div>
    </div>
    <form method="post" action="/access-control/blocked/block" class="form">
        <?= csrf_field() ?>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
            <label>
                <span>來源 IP</span>
                <input type="text" name="ip" value="<?= e(old('ip', '')) ?>" placeholder="203.0.113.25" required>
            </label>
            <label>
                <span>封鎖分鐘數</span>
                <input type="number" name="minutes" value="<?= e(old('minutes', (string) $blockMinutes)) ?>" min="1" max="43200" required>
                <span class="field-hint">上限 43200 分鐘（30 天）。</span>
            </label>
        </div>
        <label>
            <span>備註（選填）</span>
            <input type="text" name="notes" value="<?= e(old('notes', '')) ?>" maxlength="255" placeholder="例如：持續掃描登入頁">
        </label>
        <div class="form-actions">
            <button class="btn primary" type="submit">封鎖此 IP</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>近期登入失敗來源（觀察）</h2>
            <p class="muted-text">最近 <?= e((string) $windowMinutes) ?> 分鐘內登入失敗的來源 IP，尚未達自動封鎖門檻者也會列出，供你判斷是否要提前手動封鎖。</p>
        </div>
    </div>
    <?php if (!$offenders): ?>
        <p class="muted-text">近期沒有登入失敗記錄。</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>來源 IP</th>
                        <th style="text-align:right">失敗次數</th>
                        <th>最後失敗時間</th>
                        <th style="text-align:right">操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($offenders as $row): ?>
                    <?php $oip = (string) ($row['ip_address'] ?? ''); ?>
                    <tr>
                        <td><?= e($oip) ?></td>
                        <td style="text-align:right"><?= e((string) ($row['fails'] ?? 0)) ?></td>
                        <td><?= e((string) ($row['last_seen'] ?? '')) ?></td>
                        <td style="text-align:right">
                            <form method="post" action="/access-control/blocked/block">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ip" value="<?= e($oip) ?>">
                                <input type="hidden" name="minutes" value="<?= e((string) $blockMinutes) ?>">
                                <input type="hidden" name="notes" value="由觀察清單手動封鎖">
                                <button class="btn" type="submit">封鎖</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
