// Declarative contact actions carry an href template, so an add-on can add a link without any JS.
//
//   'tel:{mobile_phone|primary_phone}'   first non-empty of the two
//
// If a token resolves to nothing the action has nothing to link to, so the template resolves to null
// and the shell hides the action.

const TOKEN = /\{([^{}]+)\}/g

/**
 * Look a field up on a contact: its data columns first, then its meta.
 */
export function contactValue ( contact, key ) {
  const data = ( contact && contact.data ) || {}
  const meta = ( contact && contact.meta ) || {}
  const value = data[ key ] !== undefined && data[ key ] !== '' ? data[ key ] : meta[ key ]

  return value === undefined || value === null ? '' : String( value ).trim()
}

/**
 * @param {string} template
 * @param {object} contact
 * @returns {string|null}
 */
export function resolveHref ( template, contact ) {

  if ( ! template ) {
    return null
  }

  const phone = /^(tel|sms):/i.test( template )
  let missing = false

  const href = template.replace( TOKEN, ( _, spec ) => {
    const value = spec.split( '|' ).map( key => contactValue( contact, key.trim() ) ).find( Boolean )

    if ( ! value ) {
      missing = true
      return ''
    }

    // a phone number can't carry spaces or punctuation, anything else is URL encoded
    return phone ? value.replace( /[^\d+]/g, '' ) : encodeURIComponent( value ).replace( /%40/g, '@' )
  } )

  if ( missing ) {
    return null
  }

  // only ever link to schemes that make sense from a contact record
  return /^(tel|sms|mailto|https?):/i.test( href ) ? href : null
}
