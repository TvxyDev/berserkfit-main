const CACHE_NAME = 'berserkfit-cache-v1';
const urlsToCache = [
  'index.php',
  'css/estilo.css',
  'css/global.css',
  'css/responsive.css',
  'js/main.js',
  'assets/logotipo1.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      })
  );
});
