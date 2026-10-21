// The contact record's core panels, and the create sheets. All of these reach the app through ctx, the
// same as an add-on's would.

import { useState } from 'preact/hooks'
import { Button, Empty, ErrorMessage, Field, Input, List, ListItem, SheetFooter, SkeletonList, Textarea, Icon } from '../ui/index.jsx'
import { TaskRow, useTaskList, NewTaskSheet } from './tasks.jsx'
import { itemOf, plainText, useFetch } from '../util.js'

export function NotesPanel ( { ctx, contact } ) {
  const { __ } = ctx.i18n
  const [ text, setText ] = useState( '' )
  const [ saving, setSaving ] = useState( false )

  const { data, error, loading, reload } = useFetch( () => ctx.api.get( 'notes', {
    object_id: contact.ID,
    object_type: 'contact',
    orderby: 'timestamp',
    order: 'DESC',
    limit: 20,
  } ), [ contact.ID ] )

  const notes = ( data && data.items ) || []

  const add = async e => {
    e.preventDefault()

    if ( ! text.trim() ) {
      return
    }

    setSaving( true )

    try {
      await ctx.api.post( 'notes', { data: { object_id: contact.ID, object_type: 'contact', content: text.trim(), type: 'note', context: 'user' } } )
      setText( '' )
      ctx.toast( __( 'Note added' ) )
      await reload()
    } catch ( err ) {
      ctx.toast( err.message, 'error' )
    }

    setSaving( false )
  }

  return (
    <>
      { ctx.can( 'add_notes' ) ? (
        <form class="ghm-note-form" onSubmit={ add }>
          <Textarea placeholder={ __( 'Add a note' ) } aria-label={ __( 'Add a note' ) } value={ text } onInput={ e => setText( e.currentTarget.value ) }/>
          <Button variant="primary" type="submit" disabled={ saving || ! text.trim() } aria-label={ __( 'Save note' ) }><Icon name="check"/></Button>
        </form>
      ) : null }
      <ErrorMessage error={ error }/>
      { loading && ! notes.length ? <SkeletonList rows={ 2 }/> : null }
      { ! loading && ! error && ! notes.length ? <Empty>{ __( 'No notes yet.' ) }</Empty> : null }
      { notes.map( n => (
        <div class="ghm-note" key={ n.ID }>
          <p>{ plainText( n.data.content ) }</p>
          <small>{ n.i18n.time_diff } { __( 'ago' ) }</small>
        </div>
      ) ) }
    </>
  )
}

const PAGE = 15

/**
 * A short, recent list of what has happened with the contact: emails sent and opened, replies, form submissions,
 * pages visited. It comes from a lean endpoint (not the desktop timeline, which is megabytes for a busy contact) a
 * page at a time, newest first.
 */
export function ActivityPanel ( { ctx, contact } ) {
  const { __, sprintf } = ctx.i18n
  const path = `mobile/contacts/${ contact.ID }/activity`

  const first = useFetch( () => ctx.api.get( path, { limit: PAGE } ), [ contact.ID ] )
  const [ more, setMore ] = useState( { items: [], cursor: null, busy: false } )

  // the cursor is the last page loaded, which is the first one until more has been asked for
  const base = first.data
  const cursor = more.cursor || ( base ? { has_more: base.has_more, next_before: base.next_before } : null )

  // the cursor is inclusive, so a page can start with the entry the last one ended on
  const seen = new Set()
  const items = [ ...( base ? base.items : [] ), ...more.items ].filter( i => ! seen.has( i.id ) && seen.add( i.id ) )

  const loadMore = async () => {
    setMore( m => ( { ...m, busy: true } ) )

    try {
      const page = await ctx.api.get( path, { limit: PAGE, before: cursor.next_before } )
      setMore( m => ( { items: [ ...m.items, ...page.items ], cursor: { has_more: page.has_more, next_before: page.next_before }, busy: false } ) )
    } catch ( err ) {
      setMore( m => ( { ...m, busy: false } ) )
      ctx.toast( err.message, 'error' )
    }
  }

  return (
    <>
      <ErrorMessage error={ first.error }/>
      { first.loading && ! items.length ? <SkeletonList variant="avatar" rows={ 4 }/> : null }
      { ! first.loading && ! first.error && ! items.length ? <Empty>{ __( 'No activity yet.' ) }</Empty> : null }
      { items.length ? (
        <List>
          { items.map( i => (
            <ListItem
              leading={ <span class={ `ghm-act-icon ${ i.tone || '' }` }><Icon name={ i.icon }/></span> }
              title={ i.title }
              subtitle={ i.detail }
              meta={ sprintf( __( '%s ago' ), i.time_diff ) }
              key={ i.id }
            />
          ) ) }
        </List>
      ) : null }
      { cursor && cursor.has_more ? (
        <div class="ghm-more"><Button block onClick={ loadMore } disabled={ more.busy }>{ more.busy ? __( 'Loading…' ) : __( 'Show older' ) }</Button></div>
      ) : null }
    </>
  )
}

export function ContactTasksPanel ( { ctx, contact } ) {
  const { __ } = ctx.i18n
  const { tasks, error, loading, reload, complete } = useTaskList( ctx, {
    object_id: contact.ID,
    object_type: 'contact',
    incomplete: true,
    orderby: 'due_date',
    order: 'ASC',
    limit: 20,
  }, [ contact.ID ] )

  return (
    <>
      <ErrorMessage error={ error }/>
      { loading && ! tasks.length ? <SkeletonList variant="check" rows={ 2 }/> : null }
      { ! loading && ! error && ! tasks.length ? <Empty>{ __( 'No open tasks.' ) }</Empty> : null }
      { tasks.length ? <List>{ tasks.map( t => <TaskRow task={ t } ctx={ ctx } onComplete={ complete } key={ t.ID }/> ) }</List> : null }
      { ctx.can( 'add_tasks' ) ? (
        <div style="padding:12px 16px">
          <Button block icon="plus" onClick={ () => ctx.sheet( NewTaskSheet, { title: __( 'New task' ), contact, onCreated: reload } ) }>{ __( 'Add a task' ) }</Button>
        </div>
      ) : null }
    </>
  )
}

export function NewContactSheet ( { ctx, close } ) {
  const { __ } = ctx.i18n
  const [ f, setF ] = useState( { first_name: '', last_name: '', email: '', mobile_phone: '' } )
  const [ saving, setSaving ] = useState( false )

  const set = key => e => setF( v => ( { ...v, [ key ]: e.currentTarget.value } ) )

  const save = async e => {
    e.preventDefault()
    setSaving( true )

    try {
      const res = await ctx.api.post( 'contacts', {
        data: { first_name: f.first_name.trim(), last_name: f.last_name.trim(), email: f.email.trim(), owner_id: ctx.user.id },
        meta: f.mobile_phone.trim() ? { mobile_phone: f.mobile_phone.trim() } : {},
      } )

      const contact = itemOf( res )
      ctx.toast( __( 'Contact added' ) )
      close()

      if ( contact ) {
        ctx.navigate( `/contacts/${ contact.ID }` )
      }
    } catch ( err ) {
      setSaving( false )
      ctx.toast( err.message, 'error' )
    }
  }

  return (
    <form onSubmit={ save }>
      <Field label={ __( 'First name' ) }><Input value={ f.first_name } onInput={ set( 'first_name' ) } autocomplete="off" autofocus/></Field>
      <Field label={ __( 'Last name' ) }><Input value={ f.last_name } onInput={ set( 'last_name' ) } autocomplete="off"/></Field>
      <Field label={ __( 'Email' ) }><Input type="email" value={ f.email } onInput={ set( 'email' ) } required autocomplete="off"/></Field>
      <Field label={ __( 'Mobile phone' ) }><Input type="tel" value={ f.mobile_phone } onInput={ set( 'mobile_phone' ) } autocomplete="off"/></Field>
      <SheetFooter>
        <Button variant="primary" block type="submit" disabled={ saving }>{ __( 'Add contact' ) }</Button>
      </SheetFooter>
    </form>
  )
}
