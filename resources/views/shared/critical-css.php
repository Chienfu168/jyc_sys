:root {
    --bg: #f3f6f4;
    --surface: #ffffff;
    --line: #dfe6e2;
    --text: #18201c;
    --muted: #66736c;
    --primary: #1f7a5b;
    --primary-strong: #155f47;
    --sidebar: #13211a;
    --sidebar-soft: #1d3328;
    --danger-bg: #fff1f1;
    --danger: #9f2d2d;
    --success-bg: #edf8f1;
    --success: #226a43;
}

* { box-sizing: border-box; }
html { min-width: 0; overflow-x: hidden; }
body {
    margin: 0;
    min-height: 100vh;
    background: var(--bg);
    color: var(--text);
    font-family: "Noto Sans TC", "Microsoft JhengHei", Arial, sans-serif;
    font-size: 15px;
    overflow-x: hidden;
}
a { color: inherit; text-decoration: none; }

.app-shell {
    display: grid;
    grid-template-columns: 300px minmax(0, 1fr);
    width: 100%;
    min-height: 100vh;
}
.guest-shell { display: block; }
.guest-shell .main { width: 100vw; max-width: none; padding: 0; }
.guest-shell .content-area { width: 100vw; max-width: none; }
.sidebar {
    position: sticky;
    top: 0;
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: var(--sidebar);
    color: #ffffff;
    padding: 24px 20px;
    overflow-y: auto;
}
.brand,
.topbar,
.account,
.actions,
.page-tools,
.form-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}
.brand { gap: 12px; margin-bottom: 28px; }
.brand-mark {
    display: grid;
    place-items: center;
    flex: 0 0 42px;
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background: var(--primary);
    color: #ffffff;
    font-weight: 800;
}
.brand-text strong,
.brand-text span,
.account small,
.page-title span {
    display: block;
}
.brand-text span,
.account small,
.page-title span,
.muted-text {
    color: var(--muted);
}
.sidebar .brand-text span,
.sidebar .nav-section-title {
    color: rgba(255, 255, 255, 0.66);
}
.nav,
.nav-section {
    display: grid;
    gap: 8px;
}
.nav-section { margin-bottom: 22px; }
.nav-section-title {
    padding: 0 10px;
    font-size: 12px;
    font-weight: 700;
}
.nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 8px;
    color: rgba(255, 255, 255, 0.82);
}
.nav a.active,
.nav a:hover {
    background: var(--sidebar-soft);
    color: #ffffff;
}
.nav-icon {
    display: grid;
    place-items: center;
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    font-size: 13px;
    font-weight: 800;
}
.main {
    min-width: 0;
    padding: 24px;
}
.topbar {
    justify-content: space-between;
    margin-bottom: 18px;
}
.page-title h1 {
    margin: 0;
    font-size: 24px;
}
.content-area {
    min-width: 0;
    max-width: 1280px;
}

.panel,
.stat-card,
.auth-card {
    min-width: 0;
    padding: 18px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(24, 32, 28, 0.04);
}
.btn,
button.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 8px 14px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    color: var(--text);
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}
.btn.primary {
    border-color: var(--primary);
    background: var(--primary);
    color: #ffffff;
}
.btn.ghost {
    background: transparent;
}
.btn.small {
    min-height: 30px;
    padding: 5px 10px;
    font-size: 13px;
}
.badge {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 4px 9px;
    border-radius: 999px;
    background: #eef2f0;
    color: var(--muted);
    font-size: 12px;
    font-weight: 800;
}
.badge.ok { background: var(--success-bg); color: var(--success); }
.badge.warning { background: #fff7df; color: #8a5a00; }
.badge.danger { background: var(--danger-bg); color: var(--danger); }
.mono {
    font-family: "Consolas", "SFMono-Regular", monospace;
}
.table-wrap {
    width: 100%;
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th,
td {
    padding: 10px 9px;
    border-bottom: 1px solid var(--line);
    text-align: left;
    vertical-align: top;
}
th {
    color: var(--muted);
    font-size: 13px;
    font-weight: 800;
}
.amount {
    text-align: right;
    white-space: nowrap;
}

.purchase-detail {
    display: grid;
    gap: 18px;
}
.purchase-detail-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(190px, 260px) auto;
    align-items: start;
    gap: 18px;
    background: linear-gradient(135deg, #ffffff 0%, #f7fbf9 100%);
    border-color: #d8e6df;
}
.purchase-detail-title {
    min-width: 0;
    max-width: 760px;
}
.purchase-title-row,
.purchase-hero-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 12px;
}
.purchase-title-row { margin-bottom: 10px; }
.purchase-detail-title h2 {
    margin: 0;
    font-size: 24px;
    line-height: 1.35;
    overflow-wrap: anywhere;
}
.purchase-hero-meta {
    margin-top: 10px;
    color: var(--muted);
}
.purchase-hero-insight {
    min-width: 0;
    padding: 12px 14px;
    border: 1px solid #d8e6df;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.74);
}
.purchase-hero-insight span,
.purchase-hero-insight small {
    display: block;
    color: var(--muted);
    font-size: 12px;
}
.purchase-hero-insight strong {
    display: block;
    margin: 5px 0;
    color: var(--primary-strong);
    font-size: 17px;
    line-height: 1.35;
    overflow-wrap: anywhere;
}
.purchase-detail-actions {
    justify-content: flex-end;
    max-width: 260px;
}
.purchase-detail-actions form {
    margin: 0;
}
.purchase-summary-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
}
.purchase-summary-tile {
    min-width: 0;
    padding: 15px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--surface);
    box-shadow: 0 1px 2px rgba(24, 32, 28, 0.04);
}
.purchase-summary-primary {
    border-color: #c9ddd4;
    background: #f7fbf9;
}
.purchase-summary-vendor {
    grid-column: span 2;
}
.purchase-summary-tile span {
    display: block;
    color: var(--muted);
    font-size: 13px;
    margin-bottom: 8px;
}
.purchase-summary-tile strong {
    display: block;
    min-width: 0;
    font-size: 24px;
    line-height: 1.25;
    overflow-wrap: anywhere;
}
.purchase-summary-primary strong {
    color: var(--primary-strong);
    font-size: 26px;
}
.purchase-summary-tile strong.text-fit {
    font-size: 18px;
    line-height: 1.4;
}
.purchase-detail-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 340px);
    gap: 16px;
    align-items: start;
}
.purchase-detail-main,
.purchase-detail-sidebar {
    min-width: 0;
}
.purchase-detail-sidebar {
    display: grid;
    gap: 16px;
    position: sticky;
    top: 18px;
}
.purchase-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    margin-bottom: 14px;
}
.purchase-section-heading h3,
.purchase-side-heading h3 {
    margin: 0;
    font-size: 18px;
}
.purchase-section-heading p {
    margin: 4px 0 0;
}
.purchase-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px 24px;
}
.purchase-info-block h3 {
    margin: 0 0 8px;
    font-size: 15px;
}
.purchase-field-list {
    margin: 0;
}
.purchase-field-list div,
.purchase-narrative-list section {
    display: grid;
    grid-template-columns: 112px minmax(0, 1fr);
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--line);
}
.purchase-field-list dt,
.purchase-narrative-list h3 {
    margin: 0;
    color: var(--muted);
    font-weight: 700;
}
.purchase-field-list dd,
.purchase-narrative-list p {
    min-width: 0;
    margin: 0;
    line-height: 1.65;
    overflow-wrap: anywhere;
}
.purchase-narrative-list {
    display: grid;
    margin-top: 18px;
    border-top: 1px solid var(--line);
}
.purchase-lines-heading {
    margin-top: 20px;
}
.purchase-lines-table {
    min-width: 880px;
}
.purchase-lines-table th,
.purchase-lines-table td {
    white-space: normal;
}
.purchase-lines-table .line-name {
    font-weight: 700;
}
.purchase-lines-table .line-index {
    text-align: center;
    color: var(--muted);
}
.purchase-side-panel {
    padding: 16px;
}
.purchase-side-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}
.purchase-progress-list {
    list-style: none;
    display: grid;
    margin: 0;
    padding: 0;
}
.purchase-progress-list li {
    position: relative;
    padding: 10px 0 10px 30px;
    border-bottom: 1px solid var(--line);
}
.purchase-progress-list li:last-child {
    border-bottom: 0;
}
.purchase-progress-list li::before {
    content: "";
    position: absolute;
    left: 4px;
    top: 16px;
    width: 11px;
    height: 11px;
    border: 2px solid var(--line);
    border-radius: 999px;
    background: var(--surface);
}
.purchase-progress-list li.complete::before {
    border-color: var(--primary);
    background: var(--primary);
}
.purchase-progress-list li.current::before {
    border-color: var(--primary);
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(31, 122, 91, 0.12);
}
.purchase-progress-list li.current.danger::before {
    border-color: var(--danger);
    box-shadow: 0 0 0 4px rgba(159, 45, 45, 0.12);
}
.purchase-progress-list span {
    display: block;
    font-weight: 700;
    line-height: 1.35;
}
.purchase-progress-list small {
    display: block;
    margin-top: 3px;
    color: var(--muted);
    line-height: 1.45;
}
.purchase-check-list {
    display: grid;
    gap: 8px;
    margin: 0;
}
.purchase-check-list div {
    display: grid;
    grid-template-columns: minmax(88px, 0.9fr) minmax(0, 1.1fr);
    gap: 10px;
    align-items: center;
    padding: 10px 11px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fbfcfb;
}
.purchase-check-list div.ok { border-color: #cde5d7; background: #f4fbf7; }
.purchase-check-list div.warning { border-color: #ead8a8; background: #fffaf0; }
.purchase-check-list div.danger { border-color: #efc4c4; background: #fff6f6; }
.purchase-check-list dt {
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
}
.purchase-check-list dd {
    min-width: 0;
    margin: 0;
    font-weight: 700;
    line-height: 1.45;
    overflow-wrap: anywhere;
    text-align: right;
}

@media screen and (max-width: 900px) {
    .app-shell,
    .purchase-detail-hero,
    .purchase-summary-grid,
    .purchase-detail-layout,
    .purchase-info-grid {
        display: grid;
        grid-template-columns: 1fr;
    }
    .sidebar {
        position: static;
        height: auto;
    }
    .main {
        padding: 16px;
    }
    .purchase-summary-vendor {
        grid-column: span 1;
    }
    .purchase-detail-sidebar {
        position: static;
    }
    .purchase-detail-actions,
    .purchase-hero-insight {
        max-width: none;
    }
    .purchase-field-list div,
    .purchase-narrative-list section {
        grid-template-columns: 1fr;
        gap: 4px;
    }
}
