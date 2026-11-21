const cacheName = 'delivery-game-v5';
const assets = ['./', './index.php', './style.css', './app.js', './scoring.mjs',
  './manifest.webmanifest', './icon-192.png', './icon-512.png'];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(cacheName).then(cache => cache.addAll(assets)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(keys
    .filter(key => key.startsWith('delivery-game-') && key !== cacheName)
    .map(key => caches.delete(key)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  if (event.request.method !== 'GET' || url.origin !== self.location.origin) return;
  const asset = assets.find(path => new URL(path, self.registration.scope).pathname === url.pathname);
  if (!asset) return;
  if (event.request.mode === 'navigate') {
    event.respondWith((async () => {
      const cache = await caches.open(cacheName);
      const cacheKey = new URL(asset, self.registration.scope);
      try {
        const response = await fetch(event.request, { cache: 'no-cache' });
        if (response.ok) await cache.put(cacheKey, response.clone());
        return response;
      } catch {
        return (await cache.match(cacheKey)) || Response.error();
      }
    })());
    return;
  }
  event.respondWith(caches.open(cacheName).then(async cache =>
    (await cache.match(new URL(asset, self.registration.scope))) || fetch(event.request)
  ));
});




