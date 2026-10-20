import test from 'node:test'
import assert from 'node:assert/strict'

import { buildQuery, buildUrl, createApi, ApiError } from '../src/core/api.js'
import { matchRoute, parseHash, normalizePath, firstSegment, toHash } from '../src/core/router.js'
import { resolveHref } from '../src/core/template.js'
import { createRegistry } from '../src/core/registry.js'
import { createI18n, sprintf } from '../src/core/i18n.js'

// ---- api -------------------------------------------------------------------------------------------------------

test( 'buildQuery encodes arrays, objects and booleans the way PHP reads them', () => {
  assert.equal( buildQuery( { a: 1, b: [ 1, 2 ], c: { d: 'x y' }, e: true, f: false, g: null, h: undefined } ),
    'a=1&b[]=1&b[]=2&c[d]=x%20y&e=1&f=0' )
})

test( 'buildUrl joins a pretty root and a plain-permalink root', () => {
  assert.equal( buildUrl( 'https://x.test/wp-json/gh/v4/', '/contacts', { limit: 2 } ), 'https://x.test/wp-json/gh/v4/contacts?limit=2' )
  assert.equal( buildUrl( 'https://x.test/?rest_route=/gh/v4/', 'contacts', { limit: 2 } ), 'https://x.test/?rest_route=/gh/v4/contacts&limit=2' )
  assert.equal( buildUrl( 'https://x.test/wp-json/gh/v4/', 'contacts' ), 'https://x.test/wp-json/gh/v4/contacts' )
})

const res = ( status, body ) => ( { ok: status < 400, status, json: async () => { if ( body === undefined ) throw new Error( 'no json' ); return body }, text: async () => String( body ) } )

test( 'api sends the nonce and parses JSON', async () => {
  const calls = []
  const api = createApi( {
    root: 'https://x.test/wp-json/gh/v4/', nonce: 'abc123', nonceUrl: 'https://x.test/ajax',
    fetch: async ( url, init ) => { calls.push( { url, init } ); return res( 200, { items: [] } ) },
  } )

  assert.deepEqual( await api.get( 'contacts', { limit: 1 } ), { items: [] } )
  assert.equal( calls[ 0 ].init.headers[ 'X-WP-Nonce' ], 'abc123' )
  assert.equal( calls[ 0 ].init.method, 'GET' )
})

test( 'api refreshes an expired nonce once and retries', async () => {
  const seen = []
  const api = createApi( {
    root: 'https://x.test/wp-json/gh/v4/', nonce: 'old12345', nonceUrl: 'https://x.test/ajax',
    fetch: async ( url, init ) => {
      if ( url === 'https://x.test/ajax' ) return res( 200, 'newnonce99' )
      seen.push( init.headers[ 'X-WP-Nonce' ] )
      return seen.length === 1 ? res( 403, { code: 'rest_cookie_invalid_nonce' } ) : res( 200, { ok: 1 } )
    },
  } )

  assert.deepEqual( await api.post( 'notes', { data: {} } ), { ok: 1 } )
  assert.deepEqual( seen, [ 'old12345', 'newnonce99' ] )
})

test( 'api reports a logged out session when the nonce cannot be refreshed', async () => {
  const api = createApi( {
    root: 'https://x.test/wp-json/gh/v4/', nonce: 'old12345', nonceUrl: 'https://x.test/ajax',
    fetch: async ( url ) => url === 'https://x.test/ajax' ? res( 200, '0' ) : res( 403, { code: 'rest_cookie_invalid_nonce' } ),
  } )

  await assert.rejects( api.get( 'contacts' ), e => e instanceof ApiError && e.code === 'logged_out' )
})

test( 'api surfaces server errors, and does not retry other 403s', async () => {
  let n = 0
  const api = createApi( {
    root: 'https://x.test/wp-json/gh/v4/', nonce: 'abc123', nonceUrl: 'x',
    fetch: async () => { n++; return res( 403, { code: 'rest_forbidden', message: 'Nope' } ) },
  } )

  await assert.rejects( api.get( 'contacts' ), e => e.status === 403 && e.code === 'rest_forbidden' && e.message === 'Nope' )
  assert.equal( n, 1 )
})

// ---- router ----------------------------------------------------------------------------------------------------

test( 'parseHash splits the path and query', () => {
  assert.deepEqual( parseHash( '#/contacts/42?tab=notes&x=1' ), { path: '/contacts/42', query: { tab: 'notes', x: '1' } } )
  assert.deepEqual( parseHash( '' ), { path: '/', query: {} } )
  assert.deepEqual( parseHash( '#/' ), { path: '/', query: {} } )
})

test( 'normalizePath, firstSegment and toHash', () => {
  assert.equal( normalizePath( 'contacts/' ), '/contacts' )
  assert.equal( normalizePath( '' ), '/' )
  assert.equal( firstSegment( '/deals/7' ), 'deals' )
  assert.equal( firstSegment( '/' ), '' )
  assert.equal( toHash( '/contacts', { q: 'a b' } ), '#/contacts?q=a+b' )
})

test( 'matchRoute prefers static segments over parameters and decodes params', () => {
  const routes = [ { path: '/contacts/:id' }, { path: '/contacts/new' }, { path: '/' }, { path: '/contacts' } ]

  assert.equal( matchRoute( routes, '/contacts/new' ).route.path, '/contacts/new' )
  assert.deepEqual( matchRoute( routes, '/contacts/a%20b' ).params, { id: 'a b' } )
  assert.equal( matchRoute( routes, '/' ).route.path, '/' )
  assert.equal( matchRoute( routes, '/contacts/' ).route.path, '/contacts' )
  assert.equal( matchRoute( routes, '/nope' ), null )
  assert.equal( matchRoute( routes, '/contacts/1/extra' ), null )
})

// ---- template --------------------------------------------------------------------------------------------------

const contact = { data: { email: 'a+b@x.test', first_name: 'Ann' }, meta: { primary_phone: '(555) 123-4567', mobile_phone: '' } }

test( 'resolveHref uses the first non-empty token and cleans phone numbers', () => {
  assert.equal( resolveHref( 'tel:{mobile_phone|primary_phone}', contact ), 'tel:5551234567' )
  assert.equal( resolveHref( 'sms:{mobile_phone}', contact ), null )
  assert.equal( resolveHref( 'mailto:{email}', contact ), 'mailto:a%2Bb@x.test' )
  assert.equal( resolveHref( 'tel:+{primary_phone}', contact ), 'tel:+5551234567' )
})

test( 'resolveHref refuses unknown schemes and missing templates', () => {
  assert.equal( resolveHref( 'javascript:alert({email})', contact ), null )
  assert.equal( resolveHref( '', contact ), null )
  assert.equal( resolveHref( 'https://x.test/c/{first_name}', contact ), 'https://x.test/c/Ann' )
})

// ---- registry --------------------------------------------------------------------------------------------------

const manifest = {
  features: {
    core: { id: 'core', nav: [ { id: 'home', route: '/', priority: 1 } ], home_cards: [ { id: 'b', priority: 20 }, { id: 'a', priority: 20 }, { id: 'c', priority: 5 } ] },
    pipeline: { id: 'pipeline', script: { src: 'p.js' }, nav: [ { id: 'deals', route: '/deals', priority: 30 } ], search_providers: [ { id: 'deals', route: '/deals/:id', priority: 10 } ] },
  },
}

test( 'registry accepts only features the manifest lists, once', () => {
  const warnings = []
  const registry = createRegistry( manifest, { warn: m => warnings.push( m ) } )

  assert.equal( registry.register( 'nope', {} ), false )
  assert.equal( registry.register( 'core', {} ), true )
  assert.equal( registry.register( 'core', {} ), false )
  assert.equal( registry.register( 'pipeline', { apiVersion: 99 } ), false )
  assert.equal( warnings.length, 3 )
  assert.equal( registry.has( 'pipeline' ), false )
})

test( 'registry orders contributions by priority then id', () => {
  const registry = createRegistry( manifest )
  assert.deepEqual( registry.contributions( 'home_cards' ).map( c => c.id ), [ 'c', 'a', 'b' ] )
})

test( 'registry exposes routes and components from implementations', () => {
  const Card = () => null, Deals = () => null
  const registry = createRegistry( manifest )

  registry.register( 'core', { homeCards: { a: Card } } )
  registry.register( 'pipeline', { routes: { '/deals': { component: Deals, title: 'Deals' }, '/deals/:id': Deals } } )

  assert.equal( registry.implementation( 'core', 'home_cards', 'a' ), Card )
  assert.equal( registry.implementation( 'core', 'home_cards', 'zzz' ), null )
  assert.deepEqual( registry.routes().map( r => [ r.path, r.feature, r.title ] ), [ [ '/deals', 'pipeline', 'Deals' ], [ '/deals/:id', 'pipeline', '' ] ] )
})

test( 'registry finds the feature that owns a path before its bundle has loaded', () => {
  const registry = createRegistry( manifest )
  assert.equal( registry.featureForPath( '/deals/7' ), 'pipeline' )
  assert.equal( registry.featureForPath( '/' ), null )
  assert.equal( registry.featureForPath( '/unknown' ), null )
})

test( 'registry notifies subscribers when something registers', () => {
  const registry = createRegistry( manifest )
  let n = 0
  const off = registry.subscribe( () => n++ )
  registry.register( 'core', {} )
  off()
  registry.register( 'pipeline', {} )
  assert.equal( n, 1 )
})

// ---- i18n ------------------------------------------------------------------------------------------------------

test( 'sprintf handles %s, %d and numbered placeholders', () => {
  assert.equal( sprintf( '%s has %d notes', 'Ann', '3' ), 'Ann has 3 notes' )
  assert.equal( sprintf( '%2$s then %1$s', 'a', 'b' ), 'b then a' )
  assert.equal( sprintf( '100%%' ), '100%' )
})

test( 'i18n falls back to the original text and uses locale data when present', () => {
  const plain = createI18n()
  assert.equal( plain.__( 'Contacts' ), 'Contacts' )
  assert.equal( plain._n( '%d task', '%d tasks', 2 ), '%d tasks' )

  const es = createI18n( { groundhogg: { Contacts: [ 'Contactos' ], 'menu\u0004Home': [ 'Inicio' ], '%d task\u0000%d tasks': [ '%d tarea', '%d tareas' ] } } )
  assert.equal( es.__( 'Contacts' ), 'Contactos' )
  assert.equal( es._x( 'Home', 'menu' ), 'Inicio' )
  assert.equal( es._n( '%d task', '%d tasks', 1 ), '%d tarea' )
  assert.equal( es._n( '%d task', '%d tasks', 5 ), '%d tareas' )
})

// ---- text ------------------------------------------------------------------------------------------------------

import { escapeHtml, plainToHtml } from '../src/core/text.js'

test( 'plainToHtml makes paragraphs and line breaks, and nothing typed can be markup', () => {
  assert.equal( plainToHtml( 'Hi {first},\n\nCall me.\nThanks' ), '<p>Hi {first},</p>\n<p>Call me.<br>Thanks</p>' )
  assert.equal( plainToHtml( '  \n\n ' ), '' )
  assert.equal( plainToHtml( 'a\r\n\r\nb' ), '<p>a</p>\n<p>b</p>' )
  assert.equal( plainToHtml( '<script>alert("x")</script> & more' ), '<p>&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; more</p>' )
  assert.equal( escapeHtml( `'` ), '&#039;' )
})
