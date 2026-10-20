// The mobile app's service worker.
//
// It caches the app's own static files (the bundle and stylesheet) so the app opens instantly, and does nothing
// else on purpose. The page carries a REST nonce and every API response is live data, so neither is ever
// served from a cache. self.GH_SW = { version, assets } is prepended by PHP.

const CACHE = 'gh-mobile-' + self.GH_SW.version

self.addEventListener( 'install', event => {
  event.waitUntil(
    caches.open( CACHE ).then( cache => cache.addAll( self.GH_SW.assets ) ).then( () => self.skipWaiting() ),
  )
})

self.addEventListener( 'activate', event => {
  event.waitUntil(
    caches.keys()
      .then( keys => Promise.all( keys.filter( k => k.startsWith( 'gh-mobile-' ) && k !== CACHE ).map( k => caches.delete( k ) ) ) )
      .then( () => self.clients.claim() ),
  )
})

self.addEventListener( 'fetch', event => {
  const { request } = event

  // only the files PHP listed, matched with their version query
  if ( request.method !== 'GET' || ! self.GH_SW.assets.includes( request.url ) ) {
    return
  }

  event.respondWith(
    caches.match( request ).then( cached => cached || fetch( request ) ),
  )
})
