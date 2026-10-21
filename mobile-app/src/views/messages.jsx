// Messages: what has been received from a contact and what has been sent to them, as a conversation.
//
//   - The Replies card on Home: the contacts that have written back and haven't been read yet, newest first.
//   - The Messages panel in a contact: the conversation, a page at a time, with a way to reply.
//
// They read from the same endpoints as the desktop app's Messages and Replies (messages/unread, messages/feed and
// messages/read), so what is unread is the same in both, and the server decides what a person can see. Sending
// goes through emails/send, with the headers that keep it in the conversation.

import { useEffect, useState } from 'preact/hooks'
import { Avatar, Button, Card, Empty, ErrorMessage, Icon, List, ListItem, Pill, SkeletonList } from '../ui/index.jsx'
import { Compose } from './email.jsx'
import { messageText, replyHeaders, replyRecipients, replySubject, splitQuoted } from '../core/messages.js'
import { avatarUrl, contactName, useFetch } from '../util.js'

const PAGE = 10

/**
 * Home: who has replied and is waiting to be read. Nothing is shown when there is nothing, an empty card is noise.
 */
export function RepliesCard ( { ctx } ) {
  const { __, sprintf } = ctx.i18n
  const { data, error, loading } = useFetch( () => ctx.api.get( 'messages/unread', { limit: 5 } ), [] )
  const items = ( data && data.items ) || []

  if ( ! loading && ! error && ! items.length ) {
    return null
  }

  return (
    <Card title={ __( 'Replies' ) }>
      <ErrorMessage error={ error }/>
      { loading && ! items.length ? <SkeletonList variant="avatar" rows={ 2 }/> : null }
      { items.length ? (
        <List>
          { items.map( i => (
            <ListItem
              leading={ <Avatar name={ i.contact.name } src={ avatarUrl( { data: { gravatar: i.contact.avatar } } ) }/> }
              title={ i.contact.name }
              subtitle={ i.latest.preview || i.latest.data.subject }
              meta={ <>{ sprintf( __( '%s ago' ), i.latest.i18n.time_diff ) }{ i.unread > 1 ? <> <Pill color="red">{ i.unread }</Pill></> : null }</> }
              href={ `#/contacts/${ i.object_id }` }
              key={ i.object_id }
            />
          ) ) }
        </List>
      ) : null }
    </Card>
  )
}

/**
 * The reply to a message: to who it was from, with its subject, and in the same conversation.
 */
function ReplySheet ( { ctx, close, contact, message, onSent } ) {
  const { __ } = ctx.i18n

  return (
    <Compose
      ctx={ ctx }
      close={ close }
      contact={ contact }
      to={ replyRecipients( message ) }
      subject={ replySubject( message.data.subject, __( 'Re:' ) ) }
      headers={ replyHeaders( message ) }
      onSent={ onSent }
    />
  )
}

function NewMessageSheet ( { ctx, close, contact, onSent } ) {
  return <Compose ctx={ ctx } close={ close } contact={ contact } onSent={ onSent }/>
}

/**
 * One message. Closed it is a line, opened it is what was said, with what it was in reply to kept behind a tap.
 * Opening one that was received and not read reads it, the same as on the desktop.
 */
function Message ( { ctx, contact, item, open, onToggle, onSetRead, canReply, onReply } ) {
  const { __, sprintf } = ctx.i18n
  const [ showQuoted, setShowQuoted ] = useState( false )

  const inbound = item.data.direction === 'inbound'
  const unread = inbound && Number( item.data.is_read ) === 0
  const failed = ! inbound && item.data.status === 'failed'
  const who = inbound ? contactName( contact ) : __( 'You' )

  const parts = open ? splitQuoted( messageText( item.data.content ) ) : null

  return (
    <li class={ `ghm-msg ${ inbound ? 'in' : 'out' } ${ unread ? 'is-unread' : '' }` }>
      <button type="button" class="ghm-item" aria-expanded={ open } onClick={ onToggle }>
        <span class="ghm-act-icon"><Icon name={ inbound ? 'mail' : 'send' }/></span>
        <div class="ghm-item-body">
          <div class="ghm-item-title">{ item.data.subject || __( '(no subject)' ) }</div>
          <div class="ghm-item-sub">{ open ? who : `${ who }: ${ item.preview || '' }` }</div>
        </div>
        <div class="ghm-item-meta">
          { failed ? <Pill color="red">{ __( 'Failed' ) }</Pill> : null }
          { unread ? <span class="ghm-unread-dot" role="img" aria-label={ __( 'Not read' ) }/> : null }
          { ' ' }{ sprintf( __( '%s ago' ), item.i18n.time_diff ) }
        </div>
      </button>
      { open ? (
        <div class="ghm-msg-body">
          <p class="ghm-msg-text">{ parts.text || __( '(no message)' ) }</p>
          { parts.quoted ? (
            <>
              <button type="button" class="ghm-link-btn" onClick={ () => setShowQuoted( v => ! v ) }>{ showQuoted ? __( 'Hide earlier text' ) : __( 'Show earlier text' ) }</button>
              { showQuoted ? <p class="ghm-msg-text ghm-msg-quoted">{ parts.quoted }</p> : null }
            </>
          ) : null }
          { inbound ? (
            <div class="ghm-msg-actions">
              { canReply ? <Button variant="primary" icon="reply" onClick={ () => onReply( item ) }>{ __( 'Reply' ) }</Button> : null }
              <Button onClick={ () => onSetRead( item, unread ) }>{ unread ? __( 'Mark read' ) : __( 'Mark unread' ) }</Button>
            </div>
          ) : null }
        </div>
      ) : null }
    </li>
  )
}

/**
 * The contact's conversation, newest first, a page at a time.
 */
export function MessagesPanel ( { ctx, contact } ) {
  const { __ } = ctx.i18n
  const [ state, setState ] = useState( { items: [], next: null, hasMore: false, loading: true, busy: false, error: null } )
  const [ open, setOpen ] = useState( [] )

  const load = async next => {
    setState( s => ( { ...s, busy: !! next, loading: ! next, error: null } ) )

    try {
      const page = await ctx.api.get( 'messages/feed', { object_type: 'contact', object_id: contact.ID, limit: PAGE, ...( next || {} ) } )

      setState( s => {
        // a message that moved onto the next page while this was open would be in both
        const seen = new Set( ( next ? s.items : [] ).map( i => i.ID ) )
        const items = [ ...( next ? s.items : [] ), ...page.items.filter( i => i.kind === 'message' && ! seen.has( i.ID ) ) ]

        return { items, next: page.next, hasMore: !! page.has_more, loading: false, busy: false, error: null }
      } )
    } catch ( err ) {
      setState( s => ( { ...s, loading: false, busy: false, error: err } ) )
    }
  }

  useEffect( () => {
    load()
  }, [ contact.ID ] )

  const setRead = async ( item, read ) => {
    try {
      await ctx.api.post( 'messages/read', { message_id: item.ID, read } )

      setState( s => ( { ...s, items: s.items.map( i => i.ID === item.ID ? { ...i, data: { ...i.data, is_read: read ? 1 : 0 } } : i ) } ) )
    } catch ( err ) {
      ctx.toast( err.message, 'error' )
    }
  }

  const toggle = item => {
    const opening = ! open.includes( item.ID )

    setOpen( o => opening ? [ ...o, item.ID ] : o.filter( id => id !== item.ID ) )

    // it is read when it is opened, not when it is just there
    if ( opening && item.data.direction === 'inbound' && Number( item.data.is_read ) === 0 ) {
      setRead( item, true )
    }
  }

  // what was sent is in the conversation, and so is anything that came in meanwhile
  const sent = () => ctx.emit( 'contact:changed', { id: contact.ID } )

  // the same as the Email sheet: a contact that bounced or unsubscribed from everything can't be sent to
  const canSend = ctx.can( 'send_emails' ) && contact.is_deliverable !== false

  const reply = item => ctx.sheet( ReplySheet, { title: __( 'Reply' ), contact, message: item, onSent: sent } )
  const write = () => ctx.sheet( NewMessageSheet, { title: __( 'New message' ), contact, onSent: sent } )

  const { items, hasMore, loading, busy, error } = state

  return (
    <>
      <ErrorMessage error={ error }/>
      { canSend ? (
        <div class="ghm-more" style="padding-bottom:4px">
          <Button block icon="send" onClick={ write }>{ __( 'Write a message' ) }</Button>
        </div>
      ) : null }
      { loading && ! items.length ? <SkeletonList variant="avatar" rows={ 3 }/> : null }
      { ! loading && ! error && ! items.length ? <Empty>{ __( 'No messages yet.' ) }</Empty> : null }
      { items.length ? (
        <List>
          { items.map( item => (
            <Message
              ctx={ ctx }
              contact={ contact }
              item={ item }
              open={ open.includes( item.ID ) }
              onToggle={ () => toggle( item ) }
              onSetRead={ setRead }
              canReply={ canSend }
              onReply={ reply }
              key={ item.ID }
            />
          ) ) }
        </List>
      ) : null }
      { hasMore ? (
        <div class="ghm-more"><Button block onClick={ () => load( state.next ) } disabled={ busy }>{ busy ? __( 'Loading…' ) : __( 'Show older' ) }</Button></div>
      ) : null }
    </>
  )
}
