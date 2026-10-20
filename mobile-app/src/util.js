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
