// Minimal service worker to enable PWA install
self.addEventListener('install', (event) => {
  // Activate worker immediately after install
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  // Become available to all pages immediately
  event.waitUntil(self.clients.claim());
});

// Optional: add fetch handler later for offline/caching

