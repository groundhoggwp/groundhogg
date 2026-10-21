// Small text helpers, kept free of the UI so they can be unit tested.

export function escapeHtml ( text ) {
  return String( text )
    .replace( /&/g, '&amp;' )
    .replace( /</g, '&lt;' )
    .replace( />/g, '&gt;' )
    .replace( /"/g, '&quot;' )
    .replace( /'/g, '&#039;' )
}

/**
 * Turn what someone typed into the HTML an email needs: blank lines start a paragraph, single line breaks stay
 * line breaks, and nothing they typed can be read as markup. Merge tags such as {first} pass through untouched,
 * because the server swaps them for the contact's details when it sends.
 *
 * @param {string} text
 * @returns {string}
 */
export function plainToHtml ( text ) {
  return String( text || '' )
    .replace( /\r\n?/g, '\n' )
    .trim()
    .split( /\n{2,}/ )
    .map( paragraph => paragraph.trim() )
    .filter( Boolean )
    .map( paragraph => `<p>${ escapeHtml( paragraph ).replace( /\n/g, '<br>' ) }</p>` )
    .join( '\n' )
}
