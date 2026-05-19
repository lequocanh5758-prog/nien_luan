/**
 * Service Worker for PWA
 * Cache-first strategy for static assets
 * Network-first for API calls
 */

const CACHE_NAME = "lqa-shop-v1";
const STATIC_ASSETS = [
	"/lequocanh/",
	"/lequocanh/index.php",
	"/lequocanh/public_files/mycss.css",
	"/lequocanh/public_files/bundle.min.css",
	"/lequocanh/public_files/bundle.min.js",
	"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css",
	"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css",
];

// Install - Cache static assets
self.addEventListener("install", (event) => {
	event.waitUntil(
		caches
			.open(CACHE_NAME)
			.then((cache) => cache.addAll(STATIC_ASSETS))
			.then(() => self.skipWaiting()),
	);
});

// Activate - Clean old caches
self.addEventListener("activate", (event) => {
	event.waitUntil(
		caches
			.keys()
			.then((keys) => {
				return Promise.all(
					keys
						.filter((key) => key !== CACHE_NAME)
						.map((key) => caches.delete(key)),
				);
			})
			.then(() => self.clients.claim()),
	);
});

// Fetch - Network first for API, Cache first for static
self.addEventListener("fetch", (event) => {
	const { request } = event;
	const url = new URL(request.url);

	// Skip non-GET requests
	if (request.method !== "GET") return;

	// Network first for API calls
	if (url.pathname.includes("/api/") || url.pathname.includes(".php")) {
		event.respondWith(
			fetch(request)
				.then((response) => {
					// Cache successful responses
					if (response.ok) {
						const clone = response.clone();
						caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
					}
					return response;
				})
				.catch(() => caches.match(request)),
		);
		return;
	}

	// Cache first for static assets
	event.respondWith(
		caches.match(request).then((cached) => {
			if (cached) return cached;
			return fetch(request).then((response) => {
				if (response.ok) {
					const clone = response.clone();
					caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
				}
				return response;
			});
		}),
	);
});
