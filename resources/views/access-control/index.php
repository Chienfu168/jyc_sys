<?php
$active = 'access-control';

$mode = old('mode', $settings['mode'] ?? 'off');
$allowText = old('allow_ips', implode("\n", $settings['allow_ips'] ?? []));
$trust = old('trust_proxy_header', $settings['trust_proxy_header'] ?? '');
$allowed = (bool) ($currentDecision['allowed'] ?? true);
$logRows = $logRows ?? [];

$reasonLabels = [
    'taiwan' => '台灣 IP',
    'private' => '內部／內網位址',
    'allowlist' => '在允許清單內',
    'foreign' => '國外／非台灣 IP',
    'unknown-ip' => '無法判斷',
];
$reason = $reasonLabels[$currentDecision['reason'] ?? ''] ?? ($currentDecision['reason'] ?? '');

$modeLabels = ['off' => '未啟用', 'monitor' => '僅記錄(觀察中)', 'enforce' => '啟用阻擋'];
$modeColors = ['off' => '#9a6a00', 'monitor' => '#1d5fa8', 'enforce' => '#1b7a43'];

ob_start();
?>
<section class="stats-grid">
    <div class="stat-card">
        <span>目前模式</span>
        <strong style="color:<?= $modeColors[$mode] ?? '#333' ?>"><?= e($modeLabels[$mode] ?? $mode) ?></strong>
    </div>
    <div class="stat-card">
        <span>您的連線位址</span>
        <strong style="font-size:18px"><?= e($currentIp !== '' ? $currentIp : '未知') ?></strong>
    </div>
    <div class="stat-card">
        <span>若啟用阻擋,您會</span>
        <strong style="color:<?= $allowed ? '#1b7a43' : '#b32d2d' ?>"><?= $allowed ? '可連入' : '被阻擋' ?></strong>
    </div>
    <div class="stat-card">
        <span>判定原因</span>
        <strong style="font-size:16px"><?= e($reason) ?></strong>
    </div>
</section>

<?php if (!empty($killSwitch)): ?>
    <section class="panel" style="border-left:4px solid #9a6a00">
        <p class="muted-text" style="margin:0">
            提醒:目前 <code>.env</code> 設定了 <code>ACCESS_CONTROL_DISABLED=true</code>(緊急停用開關),
            因此即使在下方選擇「啟用阻擋」,實際上仍會放行所有連線。排除鎖門問題後,請移除該設定使管制生效。
        </p>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>自動封鎖累犯 IP</h2>
            <p class="muted-text">除了「僅限台灣 IP」的來源管制外,系統也會自動暫時封鎖短時間內大量登入失敗的來源 IP(fail2ban 式),並可手動封鎖／解除。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/access-control/blocked">管理封鎖 IP →</a>
        </div>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>連線來源管制(僅限台灣 IP)</h2>
            <p class="muted-text">屬內部使用系統。建議先用「僅記錄(觀察模式)」蒐集一段時間的連線狀況,確認不會誤擋自己人後,再切換為「啟用阻擋」。</p>
        </div>
    </div>

    <form method="post" action="/access-control" class="form">
        <?= csrf_field() ?>

        <div class="form-section">
            <h3>管制模式</h3>
            <label class="access-mode-option">
                <input type="radio" name="mode" value="off" <?= $mode === 'off' ? 'checked' : '' ?>>
                <span class="access-mode-text">
                    <strong>關閉</strong>
                    <span class="muted-text">不做任何來源管制(預設)。</span>
                </span>
            </label>
            <label class="access-mode-option">
                <input type="radio" name="mode" value="monitor" <?= $mode === 'monitor' ? 'checked' : '' ?>>
                <span class="access-mode-text">
                    <strong>僅記錄(觀察模式)— 建議先用這個</strong>
                    <span class="muted-text">不阻擋任何連線,但會把「若啟用就會被擋」的連線記錄於下方,供你日後判斷是否安全啟用。</span>
                </span>
            </label>
            <label class="access-mode-option">
                <input type="radio" name="mode" value="enforce" <?= $mode === 'enforce' ? 'checked' : '' ?>>
                <span class="access-mode-text">
                    <strong>啟用阻擋 — 僅允許台灣 IP 連入</strong>
                    <span class="muted-text">阻擋國外連線。內部／內網位址與下方允許清單一律放行。台灣 IP 範圍以 APNIC 委派資料為準。</span>
                </span>
            </label>
        </div>

        <div class="form-section">
            <h3>允許清單(例外放行)</h3>
            <label>
                <span>額外允許的 IP 或網段</span>
                <textarea name="allow_ips" rows="5" placeholder="每行一筆,可填單一 IP 或 CIDR，例如：&#10;203.0.113.25&#10;198.51.100.0/24&#10;2001:db8::/48"><?= e($allowText) ?></textarea>
                <span class="field-hint">供固定的國外辦公室、VPN 或維運人員例外連入。每行一筆,支援 IPv4／IPv6 單一位址或 CIDR 網段。不合法的項目會自動略過。</span>
            </label>
        </div>

        <div class="form-section">
            <h3>取得來源 IP 的方式</h3>
            <label>
                <span>信任的反向代理標頭</span>
                <select name="trust_proxy_header">
                    <option value="" <?= $trust === '' ? 'selected' : '' ?>>直接連線(使用 REMOTE_ADDR,建議)</option>
                    <option value="cf-connecting-ip" <?= $trust === 'cf-connecting-ip' ? 'selected' : '' ?>>Cloudflare(CF-Connecting-IP)</option>
                    <option value="x-forwarded-for" <?= $trust === 'x-forwarded-for' ? 'selected' : '' ?>>反向代理(X-Forwarded-For)</option>
                    <option value="x-real-ip" <?= $trust === 'x-real-ip' ? 'selected' : '' ?>>反向代理(X-Real-IP)</option>
                </select>
                <span class="field-hint">若系統前面有 Cloudflare 或 Nginx／負載平衡等反向代理,直接連線會取到代理的 IP,需在此選擇對應標頭才能取得真正的訪客 IP。一般虛擬主機(cPanel)請維持「直接連線」。</span>
            </label>
        </div>

        <div class="form-actions">
            <button class="btn primary" type="submit">儲存設定</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>連線記錄(會被擋的來源)</h2>
            <p class="muted-text">此處列出「在目前設定下會被阻擋」的連線來源,以 IP 聚合。觀察模式與啟用阻擋時皆會記錄。若清單中出現你自己人的 IP,請先把它加入允許清單再啟用阻擋。</p>
        </div>
        <?php if ($logRows): ?>
            <div class="actions">
                <form method="post" action="/access-control/log/clear" onsubmit="return confirm('確定要清除所有連線記錄？');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">清除記錄</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$logRows): ?>
        <p class="muted-text">尚無記錄。切換為「僅記錄(觀察模式)」或「啟用阻擋」後,會被擋的連線就會出現在這裡。</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>來源 IP</th>
                        <th>原因</th>
                        <th style="text-align:right">次數</th>
                        <th>最後出現</th>
                        <th>最後路徑</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logRows as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['ip'] ?? '')) ?></td>
                        <td><?= e($reasonLabels[$row['reason'] ?? ''] ?? (string) ($row['reason'] ?? '')) ?></td>
                        <td style="text-align:right"><?= e((string) ($row['count'] ?? 0)) ?></td>
                        <td><?= e((string) ($row['last_seen'] ?? '')) ?></td>
                        <td class="muted-text" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e((string) ($row['last_path'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="field-hint" style="margin-top:10px">記錄最多保留 1000 個不同來源 IP,以來源聚合、不記錄每一次請求的細節,避免佔用過多空間。</p>
    <?php endif; ?>
</section>

<section class="panel" style="border-left:4px solid #b32d2d">
    <div class="panel-header">
        <div>
            <h2>被鎖在門外時的解除方式</h2>
            <p class="muted-text">「啟用阻擋」時,系統會先確認「您當下的連線」不會被擋才允許儲存;仍請記住以下救援方式。「僅記錄(觀察模式)」不會阻擋任何連線,可安心使用。</p>
        </div>
    </div>
    <ol class="muted-text" style="line-height:1.9;margin:0;padding-left:1.4em">
        <li>於主機的 <code>.env</code> 設定 <code>ACCESS_CONTROL_DISABLED=true</code>,即可強制放行所有連線。</li>
        <li>或直接刪除主機上的 <code>storage/access_control.json</code>,管制即解除。</li>
        <li>內部／內網位址(如 <code>127.0.0.1</code>、<code>192.168.x.x</code>)一律可連入,不受管制影響。</li>
    </ol>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
