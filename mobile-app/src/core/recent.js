// "Recently used": what a person used last in a list (the email template they sent, the form they submitted, the tag
// they applied) goes to the top of that list next time. The server keeps the list per user and sends it with the
// manifest, and these helpers keep the copy in the browser in step while the app is open.
//
// An entry is { id, name, sub }: the name (and an optional second line) it was used under, so it can be drawn straight
// away without asking the server for it.

export const MAX_RECENT = 8

/**
 * Put something at the front of a list, moving it there if it was already in it, and keep the list short.
 *
 * @param {{id: number, name: string, sub?: string}[]} list
 * @param {{id: number, name: string, sub?: string}} item
 * @param {number} max
 * @returns {{id: number, name: string, sub: string}[]}
 */
export function touch ( list, item, max = MAX_RECENT ) {
  const entry = { id: Number( item.id ), name: String( item.name || '' ), sub: String( item.sub || '' ) }
  return [ entry, ...( list || [] ).filter( i => Number( i.id ) !== entry.id ) ].slice( 0, max )
}

/**
 * Take something out of a list, for when it no longer exists.
 */
export function drop ( list, id ) {
  return ( list || [] ).filter( i => Number( i.id ) !== Number( id ) )
}

/**
 * The recents the server sent, made safe to use whatever came through: every entry has a numeric id, and a
 * list that isn't one is an empty list.
 *
 * @param {object} raw
 * @returns {Object<string, {id: number, name: string, sub: string}[]>}
 */
export function normalizeRecent ( raw ) {
  const out = {}

  Object.keys( raw && typeof raw === 'object' ? raw : {} ).forEach( kind => {
    const list = Array.isArray( raw[ kind ] ) ? raw[ kind ] : []

    out[ kind ] = list
      .filter( i => i && Number( i.id ) > 0 )
      .map( i => ( { id: Number( i.id ), name: String( i.name || '' ), sub: String( i.sub || '' ) } ) )
      .slice( 0, MAX_RECENT )
  } )

  return out
}

/**
 * The items of a list that are not among the recents, so something is shown once and not twice.
 *
 * @param {{ID?: number|string, id?: number|string}[]} items
 * @param {{id: number}[]} recents
 */
export function withoutRecents ( items, recents ) {
  const ids = new Set( recents.map( r => Number( r.id ) ) )
  return items.filter( i => ! ids.has( Number( i.id ?? i.ID ) ) )
}

/**
 * A name as a search term for a server that searches with a regular expression, so a name with brackets in it
 * finds itself instead of being a broken pattern.
 */
export function escapeRegex ( text ) {
  return String( text ).replace( /[.*+?^${}()|[\]\\]/g, '\\$&' )
}
