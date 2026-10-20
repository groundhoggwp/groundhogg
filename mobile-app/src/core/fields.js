// The contact fields the mobile app edits, and the pure logic around them.
//
// Where a field lives matters: first name, last name and email are columns on the contact (`data`), everything
// else is meta. PATCH contacts/{id} takes both as `{ data, meta }`, and only what is sent changes.

export const EDIT_FIELDS = [
  { key: 'first_name', where: 'data' },
  { key: 'last_name', where: 'data' },
  { key: 'email', where: 'data' },
  { key: 'mobile_phone', where: 'meta' },
  { key: 'primary_phone', where: 'meta' },
  { key: 'company_name', where: 'meta' },
  { key: 'job_title', where: 'meta' },
  { key: 'street_address_1', where: 'meta' },
  { key: 'street_address_2', where: 'meta' },
  { key: 'city', where: 'meta' },
  { key: 'region', where: 'meta' },
  { key: 'postal_zip', where: 'meta' },
  { key: 'country', where: 'meta' },
]

const read = ( contact, field ) => {
  const source = ( contact && contact[ field.where ] ) || {}
  const value = source[ field.key ]
  return value === undefined || value === null ? '' : String( value ).trim()
}

/**
 * The values to start the edit form with.
 *
 * @param {object} contact a contact from the REST API
 * @returns {Object<string, string>}
 */
export function initialValues ( contact ) {
  return Object.fromEntries( EDIT_FIELDS.map( field => [ field.key, read( contact, field ) ] ) )
}

/**
 * What to send to save the form: only the fields that changed, split into `data` and `meta`. Values are trimmed,
 * so a stray space doesn't count as an edit. An empty result means there is nothing to save.
 *
 * @param {object} contact the contact as it was loaded
 * @param {Object<string, string>} values the form's values
 * @returns {{data: object, meta: object}}
 */
export function changes ( contact, values ) {
  const out = { data: {}, meta: {} }

  for ( const field of EDIT_FIELDS ) {
    if ( ! ( field.key in values ) ) {
      continue
    }

    const next = String( values[ field.key ] ?? '' ).trim()

    if ( next !== read( contact, field ) ) {
      out[ field.where ][ field.key ] = next
    }
  }

  return out
}

export const isEmpty = change => ! Object.keys( change.data ).length && ! Object.keys( change.meta ).length

/**
 * The address on one line, skipping what is blank. `countryName` turns a country code into a name.
 *
 * @param {object} contact
 * @param {(code: string) => string} countryName
 * @returns {string}
 */
export function formatAddress ( contact, countryName = code => code ) {
  const meta = ( contact && contact.meta ) || {}
  const get = key => String( meta[ key ] ?? '' ).trim()

  const cityLine = [ get( 'city' ), [ get( 'region' ), get( 'postal_zip' ) ].filter( Boolean ).join( ' ' ) ].filter( Boolean ).join( ', ' )
  const country = get( 'country' )

  return [ get( 'street_address_1' ), get( 'street_address_2' ), cityLine, country ? countryName( country ) : '' ].filter( Boolean ).join( ', ' )
}
