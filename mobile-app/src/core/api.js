// A thin client for the gh/v4 REST API.
//
// It adds the nonce, retries once when the nonce has expired (an installed app can stay open for days),
// and turns every failure into an ApiError so views only deal with one shape.

export class ApiError extends Error {
  constructor ( message, { status = 0, code = '', data = null } = {} ) {
    super( message )
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.data = data
  }
}

/**
 * Encode nested params the way PHP reads them: arrays as `key[]`, objects as `key[sub]`.
 * Booleans go as 1/0, because PHP would read the string "false" as truthy. null and undefined are skipped.
 *
 * @param {object} params
 * @returns {string}
 */
export function buildQuery ( params = {} ) {
  const parts = []

  const add = ( key, value ) => {
    if ( value === undefined || value === null ) {
      return
    }

    if ( Array.isArray( value ) ) {
      value.forEach( v => add( `${ key }[]`, v ) )
    } else if ( typeof value === 'object' ) {
      Object.keys( value ).forEach( k => add( `${ key }[${ k }]`, value[ k ] ) )
    } else {
      const v = typeof value === 'boolean' ? ( value ? 1 : 0 ) : value
      parts.push( `${ encodeURIComponent( key ).replace( /%5B/g, '[' ).replace( /%5D/g, ']' ) }=${ encodeURIComponent( v ) }` )
    }
  }

  Object.keys( params ).forEach( k => add( k, params[ k ] ) )

  return parts.join( '&' )
}

/**
 * Plain permalinks make the REST root `index.php?rest_route=/gh/v4/`, which already has a query string.
 */
export function buildUrl ( root, path, query ) {
  const url = root.replace( /\/+$/, '' ) + '/' + String( path ).replace( /^\/+/, '' )
  const qs = buildQuery( query )

  if ( ! qs ) {
    return url
  }

  return url + ( url.includes( '?' ) ? '&' : '?' ) + qs
}

/**
 * The message to show for an error response. When the server lists several errors (a form with two invalid fields),
 * they are what the person needs to read, and the generic line above them ("Your submission has errors") is not.
 *
 * @param {object|null} data the error body
 * @returns {string}
 */
export function errorMessage ( data ) {
  if ( ! data ) {
    return ''
  }

  const more = Array.isArray( data.additional_errors ) ? data.additional_errors.map( e => e && e.message ).filter( Boolean ) : []

  return more.length ? [ ...new Set( more ) ].join( ' ' ) : ( data.message || '' )
}

export function createApi ( { root, nonce, nonceUrl, fetch: fetchFn = ( ...a ) => globalThis.fetch( ...a ) } ) {
  let currentNonce = nonce
  let refreshing = null

  const refreshNonce = () => {
    // several requests can fail at once, they should share one refresh
    if ( ! refreshing ) {
      refreshing = fetchFn( nonceUrl, { credentials: 'same-origin' } )
        .then( r => r.ok ? r.text() : '' )
        .then( text => {
          text = String( text ).trim()

          // a logged out session gets 0 or -1, a nonce is alphanumeric
          if ( ! /^[a-zA-Z0-9]{6,}$/.test( text ) ) {
            throw new ApiError( 'You have been logged out.', { status: 401, code: 'logged_out' } )
          }

          currentNonce = text
        } )
        .finally( () => {
          refreshing = null
        } )
    }

    return refreshing
  }

  const request = async ( method, path, { query, body } = {}, retried = false ) => {

    const headers = { 'X-WP-Nonce': currentNonce }
    const init = { method, headers, credentials: 'same-origin' }

    if ( body !== undefined ) {
      // a FormData (a submitted form, with any files) goes as it is, and the browser sets the multipart boundary
      if ( typeof FormData !== 'undefined' && body instanceof FormData ) {
        init.body = body
      } else {
        headers[ 'Content-Type' ] = 'application/json'
        init.body = JSON.stringify( body )
      }
    }

    let res

    try {
      res = await fetchFn( buildUrl( root, path, query ), init )
    } catch ( e ) {
      throw new ApiError( 'Could not reach the server.', { code: 'network' } )
    }

    let data = null

    try {
      data = await res.json()
    } catch ( e ) {
      // not JSON, handled below
    }

    if ( res.status === 403 && data && data.code === 'rest_cookie_invalid_nonce' && ! retried ) {
      await refreshNonce()
      return request( method, path, { query, body }, true )
    }

    if ( ! res.ok ) {
      throw new ApiError( errorMessage( data ) || `Request failed (${ res.status }).`, {
        status: res.status,
        code: ( data && data.code ) || '',
        data,
      } )
    }

    if ( data === null ) {
      throw new ApiError( 'The server sent an unexpected response.', { status: res.status, code: 'bad_response' } )
    }

    return data
  }

  return {
    request,
    refreshNonce,
    get: ( path, query ) => request( 'GET', path, { query } ),
    post: ( path, body ) => request( 'POST', path, { body } ),
    postForm: ( path, formData ) => request( 'POST', path, { body: formData } ),
    patch: ( path, body ) => request( 'PATCH', path, { body } ),
    put: ( path, body ) => request( 'PUT', path, { body } ),
    delete: ( path, body ) => request( 'DELETE', path, { body } ),
  }
}
