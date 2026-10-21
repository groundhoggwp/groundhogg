// Internal forms: the forms a person fills out on behalf of a contact (a site visit report, an intake sheet).
//
// The server renders each form to HTML, pre-filled for the contact, and takes the submission at
// POST forms/{id}/admin. This file is the pure part. The DOM part, turning the HTML into something safe to show,
// is in views/forms.jsx.

/**
 * Whether the server rendered a current-style form. Older forms submit through admin-ajax and need the desktop's
 * scripts, so they aren't offered here.
 *
 * @param {string} html
 * @returns {boolean}
 */
export function isModernForm ( html ) {
  return /\bgh-form-v2\b/.test( String( html || '' ) )
}

/**
 * The forms from GET forms, as { id, name, html }, keeping the ones this app can show.
 *
 * @param {object[]} items
 * @returns {{id: number, name: string, html: string}[]}
 */
export function formEntries ( items ) {
  return ( items || [] )
    .filter( f => f && isModernForm( f.rendered ) )
    .map( f => ( { id: Number( f.ID ), name: String( f.name || '' ), html: String( f.rendered ) } ) )
}

/**
 * Say who the submission is for. A submitted form doesn't carry the contact, the request does.
 *
 * @param {FormData} data
 * @param {number|string} contactId
 * @returns {FormData}
 */
export function withContact ( data, contactId ) {
  data.set( 'contact', String( contactId ) )
  return data
}

/**
 * The fields a form uses to work out WHICH contact a submission is for. The server matches by email, so if the
 * email in the form is another contact's, the submission is saved to that contact and their details are overwritten.
 * Someone filling a form out for the contact they have open must not be able to do that by accident, so these are
 * locked in the form. Changing a contact's email is what Edit is for.
 */
export const IDENTITY_FIELDS = [ 'email' ]

export const identitySelector = () => IDENTITY_FIELDS.map( name => `[name="${ name }"]` ).join( ',' )

/**
 * Tags and attributes that have no business in a form the server rendered: scripts and styles (the app has its
 * own), and anything that runs code from an attribute or a link.
 */
export const UNSAFE_TAGS = [ 'script', 'style', 'link', 'iframe', 'object', 'embed', 'meta', 'base' ]

/**
 * Whether an attribute should be dropped from rendered form HTML.
 *
 * @param {string} name
 * @param {string} value
 * @returns {boolean}
 */
export function isUnsafeAttribute ( name, value ) {
  name = String( name ).toLowerCase()

  if ( name.startsWith( 'on' ) ) {
    return true
  }

  return [ 'href', 'src', 'action', 'formaction', 'xlink:href' ].includes( name ) && /^\s*(javascript|data|vbscript):/i.test( String( value ) )
}
