// What happens when you tap Email on a contact: three ways to get an email to them.
//
//   1. Your phone's email app (a mailto link, nothing is sent from the site)
//   2. Write an email here, sent from the site and saved to the contact's record
//   3. Send one of the site's saved email templates
//
// 2 and 3 go through the same REST endpoints as the desktop app (emails/send and emails/{id}/send), so
// sender, logging and permissions are whatever the site already does. They need send_emails (3 also needs
// view_emails) and a contact that can receive email. The server has the final say on each of those.

import { useState } from 'preact/hooks'
import { Button, Card, Empty, ErrorMessage, Field, Icon, Input, List, ListItem, SheetFooter, SkeletonList, Subhead, Textarea } from '../ui/index.jsx'
import { plainToHtml } from '../core/text.js'
import { resolveHref } from '../core/template.js'
import { withoutRecents } from '../core/recent.js'
import { contactName, useDebounced, useFetch } from '../util.js'

/**
 * The contact action handler.
 */
export function openEmail ( { contact, ctx } ) {
  ctx.sheet( EmailSheet, { title: ctx.i18n.__( 'Email' ), contact } )
}

const BackButton = ( { onClick, label } ) => (
  <div style="margin:-6px 0 10px"><Button icon="back" onClick={ onClick }>{ label }</Button></div>
)

function EmailSheet ( { ctx, close, contact } ) {
  const { __ } = ctx.i18n
  const [ view, setView ] = useState( { name: 'menu' } )

  const back = () => setView( { name: 'menu' } )

  if ( view.name === 'compose' ) {
    return <Compose ctx={ ctx } close={ close } contact={ contact } back={ back }/>
  }

  if ( view.name === 'templates' ) {
    return <Templates ctx={ ctx } close={ close } contact={ contact } back={ back }/>
  }

  const mailto = resolveHref( 'mailto:{email}', contact )
  const canSend = ctx.can( 'send_emails' )
  // bounced, blocked or marked as spam: the site won't send to them, so don't offer to
  const deliverable = contact.is_deliverable !== false
  const blocked = canSend && ! deliverable ? __( 'This contact can\'t receive email.' ) : ''

  return (
    <List>
      <ListItem
        leading={ <Icon name="external"/> }
        title={ __( 'Use your email app' ) }
        subtitle={ contact.data.email }
        href={ mailto || undefined }
        disabled={ ! mailto }
      />
      { canSend ? (
        <ListItem
          leading={ <Icon name="mail"/> }
          title={ __( 'Write an email here' ) }
          subtitle={ blocked || __( 'Sent from the site and saved to their record.' ) }
          onClick={ blocked ? undefined : () => setView( { name: 'compose' } ) }
          disabled={ !! blocked }
        />
      ) : null }
      { canSend && ctx.can( 'view_emails' ) ? (
        <ListItem
          leading={ <Icon name="note"/> }
          title={ __( 'Send an email template' ) }
          subtitle={ blocked || __( 'Pick one of your saved emails.' ) }
          onClick={ blocked ? undefined : () => setView( { name: 'templates' } ) }
          disabled={ !! blocked }
        />
      ) : null }
    </List>
  )
}

function Compose ( { ctx, close, contact, back } ) {
  const { __ } = ctx.i18n
  const [ subject, setSubject ] = useState( '' )
  const [ body, setBody ] = useState( '' )
  const [ sending, setSending ] = useState( false )

  const ready = subject.trim() && body.trim()

  const send = async e => {
    e.preventDefault()

    if ( ! ready ) {
      return
    }

    setSending( true )

    try {
      await ctx.api.post( 'emails/send', {
        to: [ contact.data.email ],
        subject: subject.trim(),
        content: plainToHtml( body ),
      } )

      ctx.toast( __( 'Email sent' ) )
      close()
    } catch ( err ) {
      setSending( false )
      ctx.toast( err.message, 'error' )
    }
  }

  return (
    <form onSubmit={ send }>
      <BackButton onClick={ back } label={ __( 'Back' ) }/>
      <p class="ghm-item-meta" style="margin-bottom:12px">{ __( 'To' ) }: { contactName( contact ) } &lt;{ contact.data.email }&gt;</p>
      <Field label={ __( 'Subject' ) }>
        <Input value={ subject } onInput={ e => setSubject( e.currentTarget.value ) } required autofocus/>
      </Field>
      <Field label={ __( 'Message' ) }>
        <Textarea style="min-height:160px" value={ body } onInput={ e => setBody( e.currentTarget.value ) } required/>
      </Field>
      <p class="ghm-item-meta" style="margin:-6px 0 14px">{ __( 'Merge tags like {first} are filled in when it is sent.' ) }</p>
      <SheetFooter>
        <Button variant="primary" block type="submit" disabled={ sending || ! ready }>{ sending ? __( 'Sending…' ) : __( 'Send email' ) }</Button>
      </SheetFooter>
    </form>
  )
}

function Templates ( { ctx, close, contact, back } ) {
  const { __ } = ctx.i18n
  const [ query, setQuery ] = useState( '' )
  const [ picked, setPicked ] = useState( null )
  const search = useDebounced( query.trim() )

  // the list needs titles and subjects, not the (very large) email bodies
  const { data, error, loading } = useFetch( () => ctx.api.get( 'emails', {
    status: 'ready',
    message_type: [ 'marketing', 'transactional' ],
    search: search || undefined,
    orderby: 'title',
    order: 'ASC',
    limit: 30,
    select: [ 'ID', 'title', 'subject', 'message_type' ],
  } ), [ search ] )

  const listed = ( data && data.items ) || []

  // what was sent last comes first, until they search for something
  const recents = search ? [] : ctx.recent.list( 'email_template' )
  const emails = search ? listed : withoutRecents( listed, recents )

  if ( picked ) {
    return <ConfirmTemplate ctx={ ctx } close={ close } contact={ contact } email={ picked } back={ () => setPicked( null ) }/>
  }

  // a recent keeps the title and subject it was sent under, which is all the confirmation needs
  const pickRecent = r => setPicked( { ID: r.id, data: { title: r.name, subject: r.sub } } )

  return (
    <>
      <BackButton onClick={ back } label={ __( 'Back' ) }/>
      <div style="margin-bottom:12px">
        <Input type="search" placeholder={ __( 'Search your emails' ) } aria-label={ __( 'Search your emails' ) } value={ query } onInput={ e => setQuery( e.currentTarget.value ) } enterkeyhint="search"/>
      </div>
      <ErrorMessage error={ error }/>
      { recents.length ? (
        <>
          <Subhead>{ __( 'Recent' ) }</Subhead>
          <Card><List>
            { recents.map( r => <ListItem title={ r.name || r.sub } subtitle={ r.sub } onClick={ () => pickRecent( r ) } trailing={ <Icon name="chevron"/> } key={ r.id }/> ) }
          </List></Card>
        </>
      ) : null }
      { loading && ! listed.length ? <SkeletonList rows={ recents.length ? 3 : 5 }/> : null }
      { ! loading && ! error && ! listed.length && ! recents.length ? <Empty>{ search ? __( 'No emails found.' ) : __( 'You have no emails ready to send.' ) }</Empty> : null }
      { emails.length ? (
        <>
          { recents.length ? <Subhead>{ __( 'All emails' ) }</Subhead> : null }
          <Card><List>
            { emails.map( e => (
              <ListItem title={ e.data.title || e.data.subject } subtitle={ e.data.subject } onClick={ () => setPicked( e ) } trailing={ <Icon name="chevron"/> } key={ e.ID }/>
            ) ) }
          </List></Card>
        </>
      ) : null }
    </>
  )
}

/**
 * Sending is outward facing and can't be undone, so it gets its own step instead of firing on the tap that picked the email.
 */
function ConfirmTemplate ( { ctx, close, contact, email, back } ) {
  const { __ } = ctx.i18n
  const [ sending, setSending ] = useState( false )

  const send = async () => {
    setSending( true )

    try {
      await ctx.api.post( `emails/${ email.ID }/send`, { to: contact.ID } )

      // sent, so it is what they'll most likely want to send next time
      ctx.recent.use( 'email_template', { id: email.ID, name: email.data.title || email.data.subject, sub: email.data.subject } )

      ctx.toast( __( 'Email sent' ) )
      close()
    } catch ( err ) {
      setSending( false )

      // the email was deleted since it was last used, so there is no point offering it again
      if ( err.status === 404 ) {
        ctx.recent.forget( 'email_template', email.ID )
      }

      ctx.toast( err.message, 'error' )
    }
  }

  return (
    <>
      <BackButton onClick={ back } label={ __( 'Back' ) }/>
      <Card><List>
        <ListItem title={ email.data.title || email.data.subject } subtitle={ __( 'Email' ) }/>
        <ListItem title={ email.data.subject } subtitle={ __( 'Subject' ) }/>
        <ListItem title={ contactName( contact ) } subtitle={ contact.data.email }/>
      </List></Card>
      <SheetFooter>
        <Button variant="primary" block onClick={ send } disabled={ sending }>{ sending ? __( 'Sending…' ) : __( 'Send now' ) }</Button>
      </SheetFooter>
    </>
  )
}
