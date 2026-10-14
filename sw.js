/* EduExams - Service Worker
 * Chiến lược: cache-first cho tài nguyên tĩnh, network-first cho điều hướng
 * và API, fallback offline cơ bản khi mất mạng.
 */
const CACHE_NAME = 'cvd-lms-v1';
const OFFLINE_URL = './offline.html';

const PRECACHE_URLS = [
  './offline.html',
  './manifest.webmanifest',
  './icons/icon-192.png',
  './icons/icon-512.png',
  './styles/main.css',
  './styles/theme-eduvn-student.css',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(PRECACHE_URLS).catch(() => undefined))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

function isStaticAsset(url) {
  return /\.(?:css|js|mjs|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot)$/i.test(url.pathname);
}

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  // Chỉ xử lý tài nguyên cùng nguồn (bỏ qua CDN, Google Fonts, ...).
  if (url.origin !== self.location.origin) {
    return;
  }

  // Điều hướng trang: network-first, fallback cache rồi tới trang offline.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          return response;
        })
        .catch(() => caches.match(request).then((cached) => cached || caches.match(OFFLINE_URL)))
    );
    return;
  }

  // Tài nguyên tĩnh: cache-first.
  if (isStaticAsset(url)) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) {
          return cached;
        }
        return fetch(request).then((response) => {
          if (response && response.status === 200 && response.type === 'basic') {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return response;
        });
      })
    );
    return;
  }

  // Còn lại (API, PHP động): network-first, fallback cache nếu có.
  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response && response.status === 200) {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
        }
        return response;
      })
      .catch(() => caches.match(request))
  );
});
