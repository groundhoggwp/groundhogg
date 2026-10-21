// A hash router. The server has one route for the app, so everything after the # is ours.
//
//   #/contacts/42?tab=notes   ->  path '/contacts/42', query { tab: 'notes' }

/**
 * Compile a pattern such as '/deals/:id' into a matcher.
 */
function compile ( pattern ) {
  const keys = []
  const source = pattern
    .replace( /\/+$/, '' )
    .split( '/' )
    .map( segment => {
      if ( segment.startsWith( ':' ) ) {
        keys.push( segment.slice( 1 ) )
        return '([^/]+)'
      }

      return segment.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' )
    } )
    .join( '/' )

  return { keys, regex: new RegExp( `^${ source || '' }/?$` ) }
}

export function normalizePath ( path ) {
  path = '/' + String( path || '' ).replace( /^#?\/*/, '' )
  return path.length > 1 ? path.replace( /\/+$/, '' ) : path
}

/**
 * Split '#/a/b?x=1' into a path and a query object.
 */
export function parseHash ( hash ) {
  const raw = String( hash || '' ).replace( /^#/, '' )
  const [ path, qs = '' ] = raw.split( '?' )
  const query = {}

  new URLSearchParams( qs ).forEach( ( value, key ) => {
    query[ key ] = value
  } )

  return { path: normalizePath( path ), query }
}

/**
 * Find the route that matches a path. Static segments beat parameters, so '/contacts/new' wins over '/contacts/:id'.
 *
 * @param {{path: string}[]} routes
 * @param {string} path
 * @returns {{route: object, params: object}|null}
 */
export function matchRoute ( routes, path ) {
  path = normalizePath( path )

  const matches = []

  for ( const route of routes ) {
    const { keys, regex } = compile( normalizePath( route.path ) )
    const m = regex.exec( path )

    if ( ! m ) {
      continue
    }

    const params = {}
    keys.forEach( ( key, i ) => {
      params[ key ] = decodeURIComponent( m[ i + 1 ] )
    } )

    matches.push( { route, params, score: keys.length } )
  }

  matches.sort( ( a, b ) => a.score - b.score )

  return matches.length ? { route: matches[ 0 ].route, params: matches[ 0 ].params } : null
}

/**
 * The first segment of a path, which is how a nav route says which feature owns the area.
 */
export function firstSegment ( path ) {
  return normalizePath( path ).split( '/' )[ 1 ] || ''
}

export function toHash ( path, query ) {
  const qs = new URLSearchParams( query || {} ).toString()
  return '#' + normalizePath( path ) + ( qs ? '?' + qs : '' )
}
