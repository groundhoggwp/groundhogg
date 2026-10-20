import { useState, useEffect } from 'preact/hooks'
import { Avatar, Card, Chip, Empty, ErrorMessage, Icon, Input, List, ListItem, Pill, Skeleton, SkeletonList } from '../ui/index.jsx'
import { Contribution } from '../contribution.jsx'
import { useRuntime } from '../runtime.jsx'
import { resolveHref, contactValue } from '../core/template.js'
import { optinColor } from '../core/optin.js'
import { formatAddress } from '../core/fields.js'
import { countryName } from '../core/countries.js'
import { fromApi } from '../core/tags.js'
import { EditContactSheet, EditTagsSheet } from './edit.jsx'
import { avatarUrl, contactName, itemOf, useDebounced, useFetch } from '../util.js'

export const ContactRow = ( { contact } ) => {
  const name = contactName( contact )
  const email = contact.data.email

  return (
    <ListItem
      leading={ <Avatar name={ name } src={ avatarUrl( contact ) }/> }
      title={ name }
      subtitle={ email && email !== name ? email : '' }
      href={ `#/contacts/${ contact.ID }` }
    />
  )
}

export function ContactsView ( { ctx } ) {
  const { __ } = ctx.i18n
  const [ query, setQuery ] = useState( '' )
  const search = useDebounced( query.trim() )

  const { data, error, loading } = useFetch( () => ctx.api.get( 'contacts', {
    search: search || undefined,
    limit: 25,
    orderby: 'ID',
    order: 'DESC',
  } ), [ search ] )

  const items = ( data && data.items ) || []

  return (
    <>
      <div class="ghm-search">
        <Input type="search" placeholder={ __( 'Search contacts' ) } value={ query } onInput={ e => setQuery( e.currentTarget.value ) } aria-label={ __( 'Search contacts' ) } enterkeyhint="search"/>
      </div>
      <ErrorMessage error={ error }/>
      <Card title={ search ? __( 'Results' ) : __( 'Recent' ) }>
        { loading && ! items.length ? <SkeletonList variant="avatar" rows={ 6 }/> : null }
        { ! loading && ! error && ! items.length ? <Empty>{ search ? __( 'No contacts found.' ) : __( 'No contacts yet.' ) }</Empty> : null }
        { items.length ? <List>{ items.map( c => <ContactRow contact={ c } key={ c.ID }/> ) }</List> : null }
      </Card>
    </>
  )
}

/**
 * The action bar: declarative actions are links, handler actions run what their feature registered.
 * The first four show and the rest go in a menu.
 */
function ContactActions ( { contact, ctx } ) {
  const { registry, loader, ctxFor } = useRuntime()
  const { __ } = ctx.i18n

  // handler actions need their feature's bundle, so load them ahead of the tap
  const all = registry.contributions( 'contact_actions' )
  useEffect( () => {
    all.filter( a => ! a.href ).forEach( a => loader.ensure( a.feature ) )
  }, [] )

  const actions = all
    .map( a => ( { ...a, link: a.href ? resolveHref( a.href, contact ) : null } ) )
    // a link with nothing to link to is hidden, a handler is always offered
    .filter( a => a.href ? a.link : true )

  const run = a => {
    const handler = registry.implementation( a.feature, 'contact_actions', a.id )
    handler ? handler( { contact, ctx: ctxFor( a.feature ) } ) : ctx.toast( __( 'That is not available right now.' ), 'error' )
  }

  const render = a => a.link ? (
    <a class="ghm-action" href={ a.link }><Icon name={ a.icon }/>{ a.label }</a>
  ) : (
    <button type="button" class="ghm-action" onClick={ () => run( a ) }><Icon name={ a.icon }/>{ a.label }</button>
  )

  const shown = actions.slice( 0, 4 )
  const rest = actions.slice( 4 )

  const openRest = () => ctx.sheet( ( { close } ) => (
    <List>{ rest.map( a => <ListItem leading={ <Icon name={ a.icon }/> } title={ a.label } href={ a.link || undefined } onClick={ a.link ? undefined : () => { close(); run( a ) } }/> ) }</List>
  ), { title: __( 'Actions' ) } )

  return (
    <div class="ghm-actions">
      { shown.map( render ) }
      { rest.length ? <button type="button" class="ghm-action" onClick={ openRest }><Icon name="more"/>{ __( 'More' ) }</button> : null }
    </div>
  )
}

export function ContactView ( { ctx, params } ) {
  const { __, _x } = ctx.i18n
  const { registry } = useRuntime()

  const { data, error, loading } = useFetch( () => ctx.api.get( `contacts/${ params.id }` ), [ params.id ] )

  // what a save sent back, which is the contact as it is now, shown without fetching it again
  const [ patched, setPatched ] = useState( null )

  // bumped when something changes, so the panels under the contact (activity, notes) load again
  const [ version, setVersion ] = useState( 0 )

  useEffect( () => {
    setPatched( null )

    return ctx.on( 'contact:changed', ( { id, item } ) => {
      if ( String( id ) !== String( params.id ) ) {
        return
      }

      if ( item ) {
        setPatched( item )
      }

      setVersion( v => v + 1 )
    } )
  }, [ params.id ] )

  const contact = patched || ( data && itemOf( data ) )

  if ( loading && ! contact ) {
    return (
      <Card aria-busy="true">
        <div class="ghm-skel-stack"><Skeleton round w={ 72 } h={ 72 }/><Skeleton w={ 180 } h={ 22 }/><Skeleton w={ 140 } h={ 14 }/></div>
        <div class="ghm-skel-actions">{ [ 0, 1, 2 ].map( i => <Skeleton w={ 84 } h={ 66 } key={ i }/> ) }</div>
      </Card>
    )
  }

  if ( error || ! contact ) {
    return <ErrorMessage error={ error || { message: __( 'Contact not found.' ) } }/>
  }

  const name = contactName( contact )
  const address = formatAddress( contact, code => countryName( code, ctx.locale ) )

  const details = [
    [ __( 'Email' ), contact.data.email, `mailto:${ contact.data.email }` ],
    [ __( 'Mobile' ), contactValue( contact, 'mobile_phone' ), resolveHref( 'tel:{mobile_phone}', contact ) ],
    [ __( 'Phone' ), contactValue( contact, 'primary_phone' ), resolveHref( 'tel:{primary_phone}', contact ) ],
    [ __( 'Company' ), contactValue( contact, 'company_name' ) ],
    [ __( 'Job title' ), contactValue( contact, 'job_title' ) ],
    [ __( 'Address' ), address ],
  ].filter( ( [ , value ] ) => value )

  // the names match the ones the desktop app shows, so they come with the same translations
  const optinNames = {
    1: _x( 'Unconfirmed', 'optin_status' ),
    2: _x( 'Confirmed', 'optin_status' ),
    3: _x( 'Unsubscribed', 'optin_status' ),
    4: _x( 'Subscribed Weekly', 'optin_status' ),
    5: _x( 'Subscribed Monthly', 'optin_status' ),
    6: _x( 'Bounced', 'optin_status' ),
    7: _x( 'Spam', 'optin_status' ),
    8: _x( 'Complained', 'optin_status' ),
    9: _x( 'Blocked', 'optin_status' ),
  }

  const optin = Number( contact.data.optin_status )
  const tags = fromApi( contact.tags )

  // the server has the last word on whether this particular contact can be edited
  const canEdit = ctx.can( 'edit_contacts' )
  const canTag = canEdit && ctx.can( 'manage_tags' )

  const editLink = canEdit
    ? <button type="button" class="ghm-link-btn" onClick={ () => ctx.sheet( EditContactSheet, { title: __( 'Edit contact' ), contact } ) }>{ __( 'Edit' ) }</button>
    : null

  const editTags = () => ctx.sheet( EditTagsSheet, { title: __( 'Tags' ), contact } )

  return (
    <>
      <Card>
        <div class="ghm-contact-head">
          <Avatar large name={ name } src={ avatarUrl( contact ) }/>
          <h2>{ name }</h2>
          { name !== contact.data.email ? <p>{ contact.data.email }</p> : null }
        </div>
        <ContactActions contact={ contact } ctx={ ctx }/>
        { tags.length || canTag ? (
          <div class="ghm-tags-row">
            { tags.map( t => <Pill key={ t.id }>{ t.name }</Pill> ) }
            { canTag ? <button type="button" class="ghm-link-btn" onClick={ editTags }>{ tags.length ? __( 'Edit tags' ) : __( 'Add tags' ) }</button> : null }
          </div>
        ) : null }
      </Card>

      <Card title={ __( 'Details' ) } action={ editLink }><List>
        { details.map( ( [ label, value, href ] ) => <ListItem title={ value } subtitle={ label } href={ href || undefined } key={ label }/> ) }
        <ListItem title={ __( 'Opt-in status' ) } meta={ <Pill color={ optinColor( optin ) }>{ optinNames[ optin ] || __( 'Unknown' ) }</Pill> }/>
      </List></Card>

      { registry.contributions( 'contact_panels' ).map( panel => (
        <Card title={ panel.label } key={ `${ panel.feature }/${ panel.id }/${ version }` }>
          <Contribution point="contact_panels" feature={ panel.feature } id={ panel.id } label={ panel.label } contact={ contact }/>
        </Card>
      ) ) }
    </>
  )
}
