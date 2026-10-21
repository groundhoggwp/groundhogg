import { useEffect, useRef, useState, useCallback } from 'preact/hooks'

/**
 * Run an async loader when the dependencies change. Out-of-order responses are dropped, so typing in a
 * search box can't show the results of an older query.
 *
 * @param {() => Promise<any>} load
 * @param {any[]} deps
 */
export function useFetch ( load, deps = [] ) {
  const [ state, setState ] = useState( { data: null, error: null, loading: true } )
  const latest = useRef( 0 )

  const run = useCallback( () => {
    const id = ++latest.current
    setState( s => ( { ...s, loading: true, error: null } ) )

    return load().then(
      data => id === latest.current && setState( { data, error: null, loading: false } ),
      error => id === latest.current && setState( s => ( { ...s, error, loading: false } ) ),
    )
  }, deps )

  useEffect( () => {
    run()
    // an unmounted view shouldn't set state
    return () => {
      latest.current++
    }
  }, [ run ] )

  return { ...state, reload: run }
}

export function useDebounced ( value, ms = 250 ) {
  const [ debounced, setDebounced ] = useState( value )

  useEffect( () => {
    const t = setTimeout( () => setDebounced( value ), ms )
    return () => clearTimeout( t )
  }, [ value, ms ] )

  return debounced
}

/**
 * The contacts this person opened most recently, newest first.
 *
 * Only their ids are remembered, so the names are read here, by the same call that lists contacts. That means the
 * list is always current, shows only contacts they can see right now, and a contact that was deleted (or reassigned
 * away from them) quietly leaves it.
 *
 * @param {object} ctx
 * @param {number} limit
 * @returns {{contacts: object[], loading: boolean, error: Error|null, expected: number}} expected is how many
 *   placeholders to show while it loads
 */
export function useRecentContacts ( ctx, limit = 5 ) {
  const ids = ctx.recent.list( 'contact' ).slice( 0, limit ).map( r => r.id )
  const key = ids.join( ',' )

  const { data, error, loading } = useFetch( () => ids.length
    ? ctx.api.get( 'contacts', { include: ids, limit: ids.length } )
    : Promise.resolve( { items: [] } ), [ key ] )

  const found = new Map( ( ( data && data.items ) || [] ).map( c => [ Number( c.ID ), c ] ) )
  const contacts = ids.map( id => found.get( id ) ).filter( Boolean )

  // ids the server didn't give back are gone, or not theirs any more: forget them
  useEffect( () => {
    if ( data ) {
      ids.filter( id => ! found.has( id ) ).forEach( id => ctx.recent.forget( 'contact', id ) )
    }
  }, [ data ] )

  return { contacts, loading, error, expected: Math.min( ids.length, 3 ) }
}

/**
 * Notes and tasks can carry HTML from the desktop editor. Show it as text.
 */
export function plainText ( html ) {
  if ( ! html ) {
    return ''
  }

  const doc = new DOMParser().parseFromString( String( html ), 'text/html' )
  return ( doc.body.textContent || '' ).trim()
}

/**
 * The name to show for a contact record from the REST API.
 */
export function contactName ( contact ) {
  const d = ( contact && contact.data ) || {}
  return ( d.full_name || `${ d.first_name || '' } ${ d.last_name || '' }` ).trim() || d.email || ''
}

/**
 * A gravatar that 404s when there isn't one, so the avatar falls back to initials instead of a generic silhouette.
 */
export function avatarUrl ( contact ) {
  const url = contact && contact.data && contact.data.gravatar

  if ( ! url ) {
    return ''
  }

  return /[?&]d=/.test( url ) ? url.replace( /([?&])d=[^&]*/, '$1d=404' ) : url + ( url.includes( '?' ) ? '&' : '?' ) + 'd=404'
}

/**
 * The single item out of a REST create/read response, whichever shape it came in.
 */
export function itemOf ( response ) {
  return response.item || ( response.items && response.items[ 0 ] ) || null
}

/**
 * Local time for the API's due_date: 'YYYY-MM-DD HH:MM:SS' from a datetime-local input value.
 */
export function toApiDate ( inputValue ) {
  return inputValue ? inputValue.replace( 'T', ' ' ) + ( inputValue.length === 16 ? ':00' : '' ) : ''
}

/**
 * The default due date for a new task: tomorrow at 09:00, in the format a datetime-local input wants.
 */
export function tomorrowMorning ( now = new Date() ) {
  const d = new Date( now.getFullYear(), now.getMonth(), now.getDate() + 1, 9, 0 )
  const pad = n => String( n ).padStart( 2, '0' )
  return `${ d.getFullYear() }-${ pad( d.getMonth() + 1 ) }-${ pad( d.getDate() ) }T09:00`
}
