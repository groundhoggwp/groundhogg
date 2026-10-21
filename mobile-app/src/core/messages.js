// What a reply needs that isn't about drawing anything, kept free of the UI so it can be unit tested.

/**
 * Whether this is a Message-ID, which is `<something@somewhere>`.
 *
 * @param {string} id
 * @returns {boolean}
 */
export function isMessageId ( id ) {
  return /^<[^<>\s@]+@[^<>\s@]+>$/.test( id || '' )
}

/**
 * The headers that make an email a reply to a message, so the contact's mail app (and the site's inbox) keeps it in
 * the same conversation: the id of the message, and of what it was in reply to. Without an id there is nothing to
 * thread on, so there are no headers, and it is sent as a new conversation.
 *
 * The same rule as the desktop app's Messages, so a reply is threaded the same whichever one it was sent from.
 *
 * @param {{data: {message_id?: string, thread_id?: string}}} message
 * @returns {Object<string,string>}
 */
export function replyHeaders ( message ) {
  const { message_id, thread_id } = ( message && message.data ) || {}

  if ( ! isMessageId( message_id ) ) {
    return {}
  }

  return {
    'In-Reply-To': message_id,
    'References': [ thread_id, message_id ].filter( ( id, i, ids ) => isMessageId( id ) && ids.indexOf( id ) === i ).join( ' ' ),
  }
}

/**
 * The subject of a reply: "Re: " in front, once.
 *
 * @param {string} subject
 * @param {string} prefix what goes in front, which is translated
 * @returns {string}
 */
export function replySubject ( subject, prefix = 'Re:' ) {
  const text = String( subject || '' ).trim()

  if ( /^re\s*:/i.test( text ) ) {
    return text
  }

  return `${ prefix } ${ text }`.trim()
}

/**
 * Where a reply goes: who the message was from.
 *
 * @param {{data: {from_address?: string}}} message
 * @returns {string[]}
 */
export function replyRecipients ( message ) {
  const from = ( ( message && message.data && message.data.from_address ) || '' ).trim()

  return from ? [ from ] : []
}

/**
 * A message from the site can be long: a whole email, with what it was in reply to under it. Show the part that was
 * written, and keep the rest a tap away. The quoted part starts at a line like "On Tue, Jan 2, Sam wrote:", or at a
 * block of lines that begin with ">".
 *
 * @param {string} text
 * @returns {{text: string, quoted: string}}
 */
export function splitQuoted ( text ) {
  const lines = String( text || '' ).replace( /\r\n?/g, '\n' ).split( '\n' )

  const at = lines.findIndex( ( line, i ) => {
    if ( /^\s*On .{5,200} wrote:\s*$/i.test( line ) ) {
      return i > 0
    }

    // two quoted lines in a row, so a single ">" in a sentence isn't taken for a quote
    return i > 0 && /^\s*>/.test( line ) && /^\s*>/.test( lines[ i + 1 ] || '' )
  } )

  // the line that says who wrote it belongs to the quote (mail apps wrap it onto two lines when it is long)
  let cut = at

  if ( cut > 0 && /wrote:\s*$/i.test( lines[ cut - 1 ] ) ) {
    if ( /^\s*On /i.test( lines[ cut - 1 ] ) ) {
      cut -= 1
    } else if ( /^\s*On /i.test( lines[ cut - 2 ] || '' ) ) {
      cut -= 2
    }
  }

  // nothing before the quote, so there is no other part to put it behind
  if ( cut < 1 ) {
    return { text: lines.join( '\n' ).trim(), quoted: '' }
  }

  return {
    text: lines.slice( 0, cut ).join( '\n' ).trim(),
    quoted: lines.slice( cut ).join( '\n' ).trim(),
  }
}

/**
 * The words of an email, with its line breaks. A message is HTML (or plain text), and the text of the HTML alone
 * runs every paragraph into the next, so the places a line would break are kept first.
 *
 * Nothing in it is ever put on the page as markup, only the text that is left.
 *
 * @param {string} html
 * @returns {string}
 */
export function messageText ( html ) {
  const source = String( html || '' )
    .replace( /<(style|script|head|title)[\s\S]*?<\/\1>/gi, '' )
    .replace( /<br\s*\/?>/gi, '\n' )
    .replace( /<\/(p|div|li|tr|h[1-6]|blockquote)>/gi, '\n\n' )

  let text

  if ( typeof DOMParser !== 'undefined' ) {
    text = new DOMParser().parseFromString( source, 'text/html' ).body.textContent || ''
  } else {
    text = source.replace( /<[^>]*>/g, '' )
  }

  return text
    .replace( /\u00a0/g, ' ' )
    .replace( /[ \t]+\n/g, '\n' )
    .replace( /\n{3,}/g, '\n\n' )
    .trim()
}
