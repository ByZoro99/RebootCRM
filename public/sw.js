const CACHE = 'rebootcrm-v1';
self.addEventListener('install', (e) => self.skipWaiting());
self.addEventListener('activate', (e) => self.clients.claim());
self.addEventListener('fetch', (e) => {
  // Network-first: la app es dinámica; el SW solo habilita la instalación.
  e.respondWith(fetch(e.request).catch(() => caches.match(e.request)));
});
