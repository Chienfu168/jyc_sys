<?php
$active = 'access-control';

$enabled = (bool) ($settings['enabled'] ?? false);
$allowText = old('allow_ips', implode("\n", $settings['allow_ips'] ?? []));
$trust = old('trust_proxy_header', $settings['trust_proxy_header'] ?? '');
$allowed = (bool) ($currentDecision['allowed'] ?? true);
$reasonLabels = [
    'taiwan' => '台灣 IP',
    'private' => '內部／內網位址',
    'allowlist' => '在允許清單內',
    'foreign' => '國外／非台灣 IP',
    'unknown-ip' => '無法判斷',
];
$reason = $reasonLabels[$currentDecision['reason'] ?? ''] ?? ($currentDecision['reason'] ?? '');

ob_start();
?>
<section class="stats-grid">
    <div class="stat-card">
        <span>目前狀態</span>
        <strong style="color:<?= $enabled ? '#1b7a43' : '#9a6a00' ?>"><?= $enabled ? '已啟用' : '未啟用' ?></strong>
    </div>
    <div class="stat-card">
        <span>您的連線位址</span>
        <strong style="font-size:18px"><?= e($currentIp !== '' ? $currentIp : '未知') ?></strong>
    </div>
    <div class="stat-card">
        <span>依目前設定判定</span>
        <strong style="color:<?= $allowed ? '#1b7a43' : '#b32d2d' ?>"><?= $allowed ? '可連入' : '會被阻擋' ?></strong>
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
            因此即使在下方啟用管制,實際上仍會放行所有連線。排除鎖門問題後,請移除該設定使管制生效。
        </p>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>連線來源管制(僅限台灣 IP)</h2>
            <p class="muted-text">屬內部使用系統,啟用後僅開放台灣境內 IP 連入,阻擋國外連線以降低遭入侵風險。</p>
        </div>
    </div>

    <form method="post" action="/access-control" class="form">
        <?= csrf_field() ?>

        <div class="form-section">
            <h3>啟用管制</h3>
            <label class="checkbox-row" style="display:flex;gap:10px;align-items:flex-start">
                <input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                <span>
                    <strong>僅允許台灣 IP 連入</strong>
                    <span class="muted-text" style="display:block">內部／內網位址與下方允許清單一律放行。台灣 IP 範圍以 APNIC 委派資料為準。</span>
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

<section class="panel" style="border-left:4px solid #b32d2d">
    <div class="panel-header">
        <div>
            <h2>被鎖在門外時的解除方式</h2>
            <p class="muted-text">為避免誤設把自己擋在外面,啟用時系統會先確認「您當下的連線」不會被擋;仍請記住以下救援方式。</p>
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
