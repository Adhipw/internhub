const CACHE_NAME = 'interhub-static-v4';
const STATIC_ASSETS = [
  '/brand/logo-mark.svg',
];

const isStaticAsset = (request) => {
  const url = new URL(request.url);

  if (url.origin !== self.location.origin || request.method !== 'GET') {
    return false;
  }

  return (
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/brand/') ||
    /\.(?:css|js|svg|png|jpg|jpeg|webp|ico|woff2?)$/i.test(url.pathname)
  );
};

const isPageOrInertiaRequest = (request) => {
  const accept = request.headers.get('accept') || '';

  return (
    request.mode === 'navigate' ||
    request.headers.has('x-inertia') ||
    accept.includes('text/html') ||
    accept.includes('application/json')
  );
};

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
  );
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then(async (cacheNames) => {
      const staleCacheNames = cacheNames.filter((cacheName) => cacheName !== CACHE_NAME);

      await Promise.all(staleCacheNames.map((cacheName) => caches.delete(cacheName)));
    })
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  if (isPageOrInertiaRequest(request) || !isStaticAsset(request)) {
    return;
  }

  event.respondWith(
    caches.match(request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      return fetch(request).then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(request, responseToCache);
          });
        }

        return networkResponse;
      });
    })
  );
});
