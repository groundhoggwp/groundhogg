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
 * A task's headline and its details from what someone typed: the first line is the summary, which is what the desktop
 * shows as the task's title, and the lines after it are the details. A first line too long for a title is cut for the
 * summary and the whole text stays as the details, so nothing is lost.
 *
 * @param {string} text
 * @returns {{summary: string, content: string}} content is plain text, see plainToHtml()
 */
export function splitSummary ( text ) {
  const lines = String( text || '' ).replace( /\r\n?/g, '\n' ).trim().split( '\n' )
  const first = lines[ 0 ].trim()

  if ( first.length > SUMMARY_MAX ) {
    return { summary: first.slice( 0, SUMMARY_MAX - 1 ).trim() + '…', content: lines.join( '\n' ).trim() }
  }

  return { summary: first, content: lines.slice( 1 ).join( '\n' ).trim() }
}

const SUMMARY_MAX = 120

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
