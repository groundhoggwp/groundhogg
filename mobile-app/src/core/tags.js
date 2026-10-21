// Staging tag changes in the tag sheet, and turning them into what PATCH contacts/{id} takes.
//
// The sheet lets someone add and remove several tags and then save once, so a change can be undone before
// it is sent. These helpers keep that bookkeeping out of the UI.

/**
 * @typedef {{id?: number, name: string}} Tag a tag. A tag without an id is new, and is created by name on save.
 */

const sameName = ( a, b ) => a.toLowerCase() === b.toLowerCase()

/**
 * The tags the contact will have once the staged changes are saved.
 *
 * @param {Tag[]} current tags the contact has
 * @param {Tag[]} added staged additions
 * @param {number[]} removed ids of staged removals
 * @returns {Tag[]}
 */
export function resultingTags ( current, added, removed ) {
  const kept = current.filter( t => ! removed.includes( t.id ) )
  const extra = added.filter( a => ! kept.some( t => ( a.id && t.id === a.id ) || sameName( t.name, a.name ) ) )
  return [ ...kept, ...extra ]
}

/**
 * Whether a tag is already on the contact (or staged), by id or by name, so it isn't offered or added twice.
 */
export function hasTag ( tags, tag ) {
  return tags.some( t => ( tag.id && t.id === tag.id ) || sameName( t.name, tag.name ) )
}

/**
 * The request body for the staged changes. Existing tags go by id, new ones by name (the server creates them).
 * Adding a tag that is already there, removing one that isn't, and adding then removing the same tag, all
 * cancel out.
 *
 * @param {Tag[]} current
 * @param {Tag[]} added
 * @param {number[]} removed
 * @returns {{add_tags: Array<number|string>, remove_tags: number[]}}
 */
export function tagPayload ( current, added, removed ) {
  const remove_tags = removed.filter( id => current.some( t => t.id === id ) )

  const add_tags = []
  const queuedNames = new Set()
  const queuedIds = new Set()

  for ( const tag of added ) {
    const already = current.some( t => ( tag.id && t.id === tag.id ) || sameName( t.name, tag.name ) )
    const queued = queuedIds.has( tag.id ) || queuedNames.has( tag.name.toLowerCase() )

    // removing a tag then adding it back is no change at all
    const cancelsRemoval = tag.id && remove_tags.includes( tag.id )

    if ( cancelsRemoval ) {
      remove_tags.splice( remove_tags.indexOf( tag.id ), 1 )
      continue
    }

    if ( ! already && ! queued ) {
      add_tags.push( tag.id || tag.name )
      queuedNames.add( tag.name.toLowerCase() )

      if ( tag.id ) {
        queuedIds.add( tag.id )
      }
    }
  }

  return { add_tags, remove_tags }
}

export const isUnchanged = payload => ! payload.add_tags.length && ! payload.remove_tags.length

/**
 * Tags from the REST API as { id, name }.
 */
export function fromApi ( tags ) {
  return ( tags || [] ).map( t => ( { id: Number( t.ID ), name: String( ( t.data && t.data.tag_name ) || '' ) } ) )
}
