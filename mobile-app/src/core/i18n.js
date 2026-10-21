// A small i18n layer with the same surface as wp.i18n, so strings written for this app extract
// the same way. Translations are Jed locale data, keyed by text domain:
//
//   { groundhogg: { '': { domain, lang }, 'Contacts': [ 'Contactos' ] } }
//
// Plural handling is the English rule (n !== 1) until locale data with plural forms is wired in.

export function sprintf ( format, ...args ) {
  let next = 0

  return String( format ).replace( /%(?:(\d+)\$)?([sd%])/g, ( match, position, type ) => {
    if ( type === '%' ) {
      return '%'
    }

    const value = args[ position ? Number( position ) - 1 : next++ ]
    return type === 'd' ? String( parseInt( value, 10 ) || 0 ) : String( value )
  } )
}

export function createI18n ( data = {}, domain = 'groundhogg' ) {

  const lookup = ( key ) => {
    const entry = data[ domain ] && data[ domain ][ key ]
    return Array.isArray( entry ) ? entry : null
  }

  return {
    domain,
    sprintf,
    __: text => ( lookup( text ) || [ text ] )[ 0 ] || text,
    _x: ( text, context ) => ( lookup( `${ context }\u0004${ text }` ) || [ text ] )[ 0 ] || text,
    _n: ( single, plural, number ) => {
      const entry = lookup( `${ single }\u0000${ plural }` )
      const index = number === 1 ? 0 : 1
      return ( entry && entry[ index ] ) || ( index === 0 ? single : plural )
    },
  }
}
