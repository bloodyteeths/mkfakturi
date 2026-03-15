// Facturino Service Worker
// Cache-first for static assets, network-first for API calls, offline fallback
const CACHE_VERSION = 'facturino-v2'
const STATIC_CACHE = `${CACHE_VERSION}-static`
const API_CACHE = `${CACHE_VERSION}-api`

// Static assets to pre-cache on install
const PRECACHE_URLS = [
  '/offline.html',
  '/favicons/android-chrome-192x192.png?v=2',
  '/favicons/android-chrome-512x512.png?v=2',
  '/favicons/favicon-32x32.png?v=2',
  '/favicons/favicon-16x16.png?v=2',
]

// Install: pre-cache essential assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => {
      return cache.addAll(PRECACHE_URLS)
    }).then(() => {
      return self.skipWaiting()
    })
  )
})

// Activate: clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name.startsWith('facturino-') && name !== STATIC_CACHE && name !== API_CACHE)
          .map((name) => caches.delete(name))
      )
    }).then(() => {
      return self.clients.claim()
    })
  )
})

// Fetch: routing strategy
self.addEventListener('fetch', (event) => {
  const { request } = event
  const url = new URL(request.url)

  // Skip non-GET requests (let them pass through)
  if (request.method !== 'GET') {
    return
  }

  // Skip cross-origin requests
  if (url.origin !== self.location.origin) {
    return
  }

  // API requests: network-first with cache fallback
  if (url.pathname.startsWith('/api/') || url.pathname === '/bootstrap') {
    event.respondWith(networkFirst(request, API_CACHE))
    return
  }

  // Static assets (JS, CSS, images, fonts): cache-first
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirst(request, STATIC_CACHE))
    return
  }

  // Navigation requests: network-first with offline fallback
  if (request.mode === 'navigate') {
    event.respondWith(navigationHandler(request))
    return
  }

  // Everything else: network-first
  event.respondWith(networkFirst(request, STATIC_CACHE))
})

// Cache-first strategy (for static assets)
async function cacheFirst(request, cacheName) {
  const cached = await caches.match(request)
  if (cached) {
    return cached
  }

  try {
    const response = await fetch(request)
    if (response.ok) {
      const cache = await caches.open(cacheName)
      cache.put(request, response.clone())
    }
    return response
  } catch (error) {
    return new Response('', { status: 503, statusText: 'Service Unavailable' })
  }
}

// Network-first strategy (for API and dynamic content)
async function networkFirst(request, cacheName) {
  try {
    const response = await fetch(request)
    if (response.ok) {
      const cache = await caches.open(cacheName)
      cache.put(request, response.clone())
    }
    return response
  } catch (error) {
    const cached = await caches.match(request)
    if (cached) {
      return cached
    }
    return new Response(JSON.stringify({ error: 'offline' }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' },
    })
  }
}

// Navigation handler with offline fallback
async function navigationHandler(request) {
  try {
    const response = await fetch(request)
    if (response.ok) {
      const cache = await caches.open(STATIC_CACHE)
      cache.put(request, response.clone())
    }
    return response
  } catch (error) {
    // Try cached version of the page first
    const cached = await caches.match(request)
    if (cached) {
      return cached
    }
    // Fall back to offline page
    return caches.match('/offline.html')
  }
}

// Check if a path is a static asset
function isStaticAsset(pathname) {
  return /\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|webp)(\?.*)?$/.test(pathname) ||
    pathname.startsWith('/build/') ||
    pathname.startsWith('/favicons/')
}

// Background sync for failed POST requests
self.addEventListener('sync', (event) => {
  if (event.tag === 'facturino-sync') {
    event.waitUntil(replayFailedRequests())
  }
})

// Replay queued requests from IndexedDB
async function replayFailedRequests() {
  // Open IndexedDB
  const db = await openSyncDB()
  const tx = db.transaction('requests', 'readonly')
  const store = tx.objectStore('requests')
  const requests = await getAllFromStore(store)

  for (const entry of requests) {
    try {
      await fetch(entry.url, {
        method: entry.method,
        headers: entry.headers,
        body: entry.body,
      })
      // Remove from queue on success
      const deleteTx = db.transaction('requests', 'readwrite')
      deleteTx.objectStore('requests').delete(entry.id)
    } catch (error) {
      // Will retry on next sync
      break
    }
  }
}

function openSyncDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('facturino-sync', 1)
    request.onupgradeneeded = (event) => {
      event.target.result.createObjectStore('requests', { keyPath: 'id', autoIncrement: true })
    }
    request.onsuccess = () => resolve(request.result)
    request.onerror = () => reject(request.error)
  })
}

function getAllFromStore(store) {
  return new Promise((resolve, reject) => {
    const request = store.getAll()
    request.onsuccess = () => resolve(request.result)
    request.onerror = () => reject(request.error)
  })
}
