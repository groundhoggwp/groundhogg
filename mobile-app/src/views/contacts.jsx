import { useState, useEffect } from 'preact/hooks'
import { Avatar, Card, Empty, ErrorMessage, Icon, Input, List, ListItem, Pill, Skeleton, SkeletonList } from '../ui/index.jsx'
import { Contribution } from '../contribution.jsx'
import { useRuntime } from '../runtime.jsx'
import { resolveHref, contactValue } from '../core/template.js'
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
  const { __ } = ctx.i18n
  const { registry } = useRuntime()

  const { data, error, loading } = useFetch( () => ctx.api.get( `contacts/${ params.id }` ), [ params.id ] )
  const contact = data && itemOf( data )

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
  const details = [
    [ __( 'Email' ), contact.data.email, `mailto:${ contact.data.email }` ],
    [ __( 'Mobile' ), contactValue( contact, 'mobile_phone' ), resolveHref( 'tel:{mobile_phone}', contact ) ],
    [ __( 'Phone' ), contactValue( contact, 'primary_phone' ), resolveHref( 'tel:{primary_phone}', contact ) ],
  ].filter( ( [ , value ] ) => value )

  const tags = ( contact.tags || [] ).map( t => t.data.tag_name )

  return (
    <>
      <Card>
        <div class="ghm-contact-head">
          <Avatar large name={ name } src={ avatarUrl( contact ) }/>
          <h2>{ name }</h2>
          { name !== contact.data.email ? <p>{ contact.data.email }</p> : null }
        </div>
        <ContactActions contact={ contact } ctx={ ctx }/>
        { tags.length ? <div class="ghm-tags">{ tags.map( t => <Pill key={ t }>{ t }</Pill> ) }</div> : null }
      </Card>

      { details.length ? (
        <Card title={ __( 'Details' ) }><List>
          { details.map( ( [ label, value, href ] ) => <ListItem title={ value } subtitle={ label } href={ href || undefined }/> ) }
        </List></Card>
      ) : null }

      { registry.contributions( 'contact_panels' ).map( panel => (
        <Card title={ panel.label } key={ `${ panel.feature }/${ panel.id }` }>
          <Contribution point="contact_panels" feature={ panel.feature } id={ panel.id } label={ panel.label } contact={ contact }/>
        </Card>
      ) ) }
    </>
  )
}
