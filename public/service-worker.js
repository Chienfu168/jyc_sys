/*
 * 基金會管理系統 Service Worker。
 *
 * 本系統為需登入、含 CSRF 與個人化資料的後台,因此刻意採「保守」策略:
 *  - 只處理 GET;POST/表單一律不攔截,避免破壞 CSRF 與寫入。
 *  - 不快取任何 HTML 頁面(避免在共用裝置外洩他人資料或顯示過期畫面),
 *    導覽採 network-first,離線時才回傳離線頁。
 *  - 僅對靜態資源(/assets/ 下的 CSS、圖示、manifest)做快取,
 *    採 stale-while-revalidate 讓載入更快、離線仍可用。
 *
 * 版本升級:提高 CACHE_VERSION 即可讓舊快取於 activate 時清除。
 */
const CACHE_VERSION = 'v1';
const STATIC_CACHE = 'jyc-static-' + CACHE_VERSION;

// 以 SW 所在位置為基準解析路徑,支援安裝於子目錄的情況。
const OFFLINE_URL = new URL('offline.html', self.location).toString();

const PRECACHE_URLS = [
    OFFLINE_URL,
    new URL('manifest.webmanifest', self.location).toString(),
    new URL('assets/img/icon-192.png', self.location).toString(),
    new URL('assets/img/icon-512.png', self.location).toString(),
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .catch(() => undefined)
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== STATIC_CACHE).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

function isStaticAsset(url) {
    return url.origin === self.location.origin
        && (url.pathname.includes('/assets/') || url.pathname.endsWith('/manifest.webmanifest'));
}

// stale-while-revalidate:先回快取(若有),同時於背景更新快取。
async function staleWhileRevalidate(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response && response.ok && response.type === 'basic') {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => undefined);
    return cached || network || fetch(request);
}

// 導覽採 network-first;離線時回傳離線頁,不快取任何登入後 HTML。
async function handleNavigate(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const cache = await caches.open(STATIC_CACHE);
        const offline = await cache.match(OFFLINE_URL);
        return offline || new Response('離線中', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // 只處理 GET;其餘(POST 表單、登出等)交回瀏覽器,保護 CSRF 與寫入。
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // 僅處理同源請求。
    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigate(request));
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});
