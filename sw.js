/**
 * Service worker: keeps the shell and already-read pages available offline.
 *
 * Navigations use network-first (so content stays fresh on the college wifi)
 * and fall back to the cached copy — then to the cached home page — when the
 * device is offline. Static assets use cache-first.
 */
const CACHE = 'rvcman-v1';
const SCOPE = new URL(self.registration.scope).pathname;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll([
      SCOPE,
      SCOPE + 'assets/app.css',
      SCOPE + 'assets/app.js',
    ])).then(() => self.skipWaiting()).catch(() => {})
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET' || !request.url.startsWith(self.location.origin)) {
    return;
  }
  // Never cache admin screens — they are per-session and change constantly.
  if (new URL(request.url).pathname.startsWith(SCOPE + 'admin')) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const copy = response.clone();
          caches.open(CACHE).then((cache) => cache.put(request, copy));
          return response;
        })
        .catch(() => caches.match(request).then((hit) => hit || caches.match(SCOPE)))
    );
    return;
  }

  event.respondWith(
    caches.match(request).then((hit) => hit || fetch(request).then((response) => {
      if (response.ok) {
        const copy = response.clone();
        caches.open(CACHE).then((cache) => cache.put(request, copy));
      }
      return response;
    }).catch(() => new Response('', { status: 504, statusText: 'offline' })))
  );
});
