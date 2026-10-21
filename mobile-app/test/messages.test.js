import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'
import { isMessageId, messageText, replyHeaders, replyRecipients, replySubject, splitQuoted } from '../src/core/messages.js'

const src = path.join( path.dirname( fileURLToPath( import.meta.url ) ), '../src' )

/* ---- what makes an email a reply ------------------------------------------------------------------------ */

test( 'a reply is threaded on the id of the message and of what it was in reply to', () => {
  const headers = replyHeaders( { data: { message_id: '<b@example.com>', thread_id: '<a@example.com>' } } )

  assert.deepEqual( headers, { 'In-Reply-To': '<b@example.com>', 'References': '<a@example.com> <b@example.com>' } )
} )

test( 'the first message of a conversation is its own thread, and is not listed twice', () => {
  const same = replyHeaders( { data: { message_id: '<a@example.com>', thread_id: '<a@example.com>' } } )
  const none = replyHeaders( { data: { message_id: '<a@example.com>' } } )

  assert.equal( same.References, '<a@example.com>' )
  assert.equal( none.References, '<a@example.com>' )
} )

test( 'a thread id that is not an id is left out, and without an id there is nothing to thread on', () => {
  assert.deepEqual( replyHeaders( { data: { message_id: '<a@example.com>', thread_id: 'not an id' } } ), {
    'In-Reply-To': '<a@example.com>',
    'References': '<a@example.com>',
  } )

  assert.deepEqual( replyHeaders( { data: { message_id: 'nope' } } ), {} )
  assert.deepEqual( replyHeaders( { data: {} } ), {} )
  assert.deepEqual( replyHeaders( null ), {} )
} )

test( 'only <local@host> is a message id', () => {
  assert.ok( isMessageId( '<abc123@example.com>' ) )

  for ( const bad of [ '', 'abc@example.com', '<abc>', '<a b@example.com>', '<a@b> <c@d>', undefined ] ) {
    assert.equal( isMessageId( bad ), false, `${ bad }` )
  }
} )

test( 'the subject of a reply starts with Re: once', () => {
  assert.equal( replySubject( 'Quote for the roof' ), 'Re: Quote for the roof' )
  assert.equal( replySubject( 'Re: Quote for the roof' ), 'Re: Quote for the roof' )
  assert.equal( replySubject( 're:Quote' ), 're:Quote' )
  assert.equal( replySubject( '' ), 'Re:' )
  assert.equal( replySubject( 'Hello', 'Antw:' ), 'Antw: Hello' )
} )

test( 'a reply goes to who the message was from', () => {
  assert.deepEqual( replyRecipients( { data: { from_address: ' sam@example.com ' } } ), [ 'sam@example.com' ] )
  assert.deepEqual( replyRecipients( { data: {} } ), [] )
} )

/* ---- reading a message ---------------------------------------------------------------------------------- */

test( 'what was written is told apart from what it was in reply to', () => {
  const text = [
    'Yes, Tuesday works.',
    '',
    'On Mon, 1 Jan 2026 at 09:00, Alex <alex@example.com> wrote:',
    '> Can we meet on Tuesday?',
    '> Alex',
  ].join( '\n' )

  const parts = splitQuoted( text )

  assert.equal( parts.text, 'Yes, Tuesday works.' )
  assert.match( parts.quoted, /^On Mon, 1 Jan 2026/ )
  assert.match( parts.quoted, /Can we meet on Tuesday\?/ )
} )

test( 'a block of quoted lines is a quote, one line that starts with > is not', () => {
  assert.equal( splitQuoted( 'Thanks\n> a\n> b' ).text, 'Thanks' )

  const single = splitQuoted( 'I said\n> hello\nand left' )
  assert.equal( single.quoted, '' )
  assert.equal( single.text, 'I said\n> hello\nand left' )
} )

test( 'a message that is all quote still shows, and plain text is left alone', () => {
  assert.deepEqual( splitQuoted( 'On Mon, 1 Jan 2026, Alex wrote:\n> hi\n> there' ), { text: 'On Mon, 1 Jan 2026, Alex wrote:\n> hi\n> there', quoted: '' } )
  assert.deepEqual( splitQuoted( 'Just this.' ), { text: 'Just this.', quoted: '' } )
  assert.deepEqual( splitQuoted( '' ), { text: '', quoted: '' } )
} )

test( 'the text of an email keeps its paragraphs and drops its markup', () => {
  const html = '<style>p{color:red}</style><p>Hi <b>Sam</b>,</p><p>See you<br>Tuesday.</p><div>Alex</div>'

  assert.equal( messageText( html ), 'Hi Sam,\n\nSee you\nTuesday.\n\nAlex' )
} )

test( 'a message is only ever text, scripts and all', () => {
  const text = messageText( '<p>Hello</p><script>alert(1)</script><img src=x onerror=alert(2)>' )

  assert.equal( text, 'Hello' )
  assert.equal( messageText( null ), '' )
} )

/* ---- where it is in the app ----------------------------------------------------------------------------- */

test( 'core registers the Replies card and the Messages panel', () => {
  const core = readFileSync( path.join( src, 'features/core.jsx' ), 'utf8' )

  assert.match( core, /replies: RepliesCard/ )
  assert.match( core, /messages: MessagesPanel/ )
} )

test( 'a reply is sent by the same composer as an email, with its headers', () => {
  const email = readFileSync( path.join( src, 'views/email.jsx' ), 'utf8' )
  const messages = readFileSync( path.join( src, 'views/messages.jsx' ), 'utf8' )

  assert.match( email, /export function Compose/ )
  assert.match( email, /headers && Object\.keys\( headers \)\.length/ )
  assert.match( messages, /headers={ replyHeaders\( message \) }/ )
} )

test( 'a long "who wrote it" line that was wrapped onto two lines goes with the quote', () => {
  const text = [
    'Sounds good.',
    '',
    'On Mon, 1 Jan 2026 at 09:00, Alexandra Longname <',
    'alexandra@example.com> wrote:',
    '> Shall we?',
    '> Alex',
  ].join( '\n' )

  const parts = splitQuoted( text )

  assert.equal( parts.text, 'Sounds good.' )
  assert.match( parts.quoted, /^On Mon/ )
} )
