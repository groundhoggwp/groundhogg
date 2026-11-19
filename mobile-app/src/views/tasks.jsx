import { useEffect, useState } from 'preact/hooks'
import { plainToHtml, splitSummary } from '../core/text.js'
import { Avatar, Button, Card, Chip, Empty, ErrorMessage, Field, Icon, Input, List, ListItem, Pill, Select, SheetFooter, SkeletonList, Textarea } from '../ui/index.jsx'
import { avatarUrl, contactName, plainText, useDebounced, useFetch, useRecentContacts, toApiDate, tomorrowMorning } from '../util.js'

/**
 * How soon a task is due, as a pill.
 */
function DuePill ( { task, ctx } ) {
  const { __, sprintf } = ctx.i18n

  if ( task.is_complete ) {
    return <Pill color="green">{ __( 'Done' ) }</Pill>
  }

  if ( task.is_overdue ) {
    return <Pill color="red">{ __( 'Overdue' ) }</Pill>
  }

  if ( task.is_due_today ) {
    return <Pill color="amber">{ __( 'Today' ) }</Pill>
  }

  return <Pill>{ sprintf( __( 'In %s' ), task.i18n.due_in ) }</Pill>
}

const CompleteButton = ( { done, onClick, label } ) => (
  <button type="button" class={ `ghm-check ${ done ? 'is-done' : '' }` } aria-label={ label } aria-pressed={ done } onClick={ onClick }>
    { done ? <Icon name="check"/> : null }
  </button>
)

export function TaskRow ( { task, ctx, onComplete, showContact } ) {
  const text = plainText( task.data.summary ) || plainText( task.data.content ) || ctx.i18n.__( 'Task' )
  const associated = task.associated || {}
  const link = showContact && associated.type === 'contact' && ( associated.id || task.data.object_id ) ? `#/contacts/${ associated.id || task.data.object_id }` : undefined

  return (
    <ListItem
      control={ <CompleteButton done={ task.is_complete } label={ ctx.i18n.__( 'Mark complete' ) } onClick={ () => onComplete( task ) }/> }
      title={ text }
      subtitle={ showContact && associated.name ? associated.name : '' }
      meta={ <DuePill task={ task } ctx={ ctx }/> }
      href={ link }
    />
  )
}

/**
 * Complete a task. The row leaves the list straight away and comes back if the server refuses.
 *
 * The list loads again when a task is created, from wherever it was, so one made from the Create menu shows up in
 * the Tasks screen and the My tasks card without leaving and coming back.
 */
export function useTaskList ( ctx, query, deps ) {
  const { data, error, loading, reload } = useFetch( () => ctx.api.get( 'tasks', query ), deps )
  const [ done, setDone ] = useState( {} )

  useEffect( () => ctx.on( 'task:created', reload ), [ reload ] )

  const complete = task => {
    setDone( d => ( { ...d, [ task.ID ]: true } ) )

    const { __ } = ctx.i18n

    ctx.api.patch( `tasks/${ task.ID }/complete` ).then(
      // completing is one tap, as it was, and how it went is there for whoever wants to say, which is what the desktop asks
      () => ctx.toast( __( 'Task completed' ), '', { label: __( 'Add note' ), onClick: () => ctx.sheet( TaskOutcomeSheet, { title: __( 'How did it go?' ), task } ) } ),
      e => {
        setDone( d => ( { ...d, [ task.ID ]: false } ) )
        ctx.toast( e.message, 'error' )
      },
    )
  }

  const tasks = ( ( data && data.items ) || [] ).filter( t => ! done[ t.ID ] )

  return { tasks, error, loading, reload, complete }
}

export const myTasksQuery = ctx => ( {
  user_id: ctx.user.id,
  incomplete: true,
  orderby: 'due_date',
  order: 'ASC',
  limit: 50,
} )

export function TasksView ( { ctx } ) {
  const { __ } = ctx.i18n
  const { tasks, error, loading, complete } = useTaskList( ctx, myTasksQuery( ctx ), [] )

  const groups = [
    [ __( 'Overdue' ), tasks.filter( t => t.is_overdue ) ],
    [ __( 'Today' ), tasks.filter( t => ! t.is_overdue && t.is_due_today ) ],
    [ __( 'Upcoming' ), tasks.filter( t => ! t.is_overdue && ! t.is_due_today ) ],
  ].filter( ( [ , items ] ) => items.length )

  return (
    <>
      <ErrorMessage error={ error }/>
      { loading && ! tasks.length ? <Card><SkeletonList variant="check" rows={ 4 }/></Card> : null }
      { ! loading && ! error && ! tasks.length ? <Card><Empty>{ __( 'Nothing to do. Enjoy it.' ) }</Empty></Card> : null }
      { groups.map( ( [ title, items ] ) => (
        <Card title={ title } key={ title }>
          <List>{ items.map( t => <TaskRow task={ t } ctx={ ctx } onComplete={ complete } showContact key={ t.ID }/> ) }</List>
        </Card>
      ) ) }
    </>
  )
}

/**
 * Choose the contact a task is about: the ones they opened lately until they type, then the ones that match.
 * It isn't a Field, whose label would take a tap on a result as a tap on the search box.
 */
function ContactField ( { ctx, value, onChange } ) {
  const { __ } = ctx.i18n
  const [ query, setQuery ] = useState( '' )
  const search = useDebounced( query.trim() )
  const recent = useRecentContacts( ctx, 5 )

  const { data, loading } = useFetch( () => search
    ? ctx.api.get( 'contacts', { search, limit: 6 } )
    : Promise.resolve( { items: [] } ), [ search ] )

  if ( value ) {
    return (
      <div class="ghm-field">
        <span>{ __( 'Contact' ) }</span>
        <Chip onRemove={ () => onChange( null ) } removeLabel={ __( 'Remove' ) }>{ contactName( value ) }</Chip>
      </div>
    )
  }

  const options = search ? ( ( data && data.items ) || [] ) : recent.contacts

  return (
    <div class="ghm-field">
      <span>{ __( 'Contact (optional)' ) }</span>
      <Input type="search" placeholder={ __( 'Search contacts' ) } value={ query } onInput={ e => setQuery( e.currentTarget.value ) } onKeyDown={ e => e.key === 'Enter' && e.preventDefault() } enterkeyhint="search"/>
      { options.length ? (
        <List>
          { options.map( c => (
            <ListItem
              key={ c.ID }
              leading={ <Avatar name={ contactName( c ) } src={ avatarUrl( c ) }/> }
              title={ contactName( c ) }
              subtitle={ c.data.email && c.data.email !== contactName( c ) ? c.data.email : '' }
              onClick={ () => {
                onChange( c )
                setQuery( '' )
              } }
            />
          ) ) }
        </List>
      ) : null }
      { search && ! loading && ! options.length ? <Empty>{ __( 'No contacts found.' ) }</Empty> : null }
    </div>
  )
}

/**
 * What happened with a task that was just completed: an outcome from the site's list and a note, which go on the task
 * the way the desktop's completion prompt puts them.
 */
export function TaskOutcomeSheet ( { ctx, close, task } ) {
  const { __ } = ctx.i18n
  const [ outcome, setOutcome ] = useState( '' )
  const [ note, setNote ] = useState( '' )
  const [ saving, setSaving ] = useState( false )

  // a site without outcomes just has the note
  const { data } = useFetch( () => ctx.api.get( 'tasks/outcomes' ).catch( () => ( { outcomes: [] } ) ), [] )
  const outcomes = ( data && data.outcomes ) || []

  const save = async e => {
    e.preventDefault()

    if ( ! outcome && ! note.trim() ) {
      close()
      return
    }

    setSaving( true )

    try {
      await ctx.api.patch( `tasks/${ task.ID }`, { data: { activity: 1, type: 'task_complete', outcome, note: note.trim() ? plainToHtml( note ) : '' } } )
      ctx.toast( __( 'Note added' ) )
      close()
    } catch ( err ) {
      setSaving( false )
      ctx.toast( err.message, 'error' )
    }
  }

  return (
    <form onSubmit={ save }>
      { outcomes.length ? (
        <Field label={ __( 'What happened?' ) }>
          <Select value={ outcome } onChange={ e => setOutcome( e.currentTarget.value ) } placeholder={ __( 'Select an outcome' ) } options={ outcomes.map( o => ( { value: o, label: o } ) ) }/>
        </Field>
      ) : null }
      <Field label={ __( 'Any more to add?' ) }>
        <Textarea value={ note } onInput={ e => setNote( e.currentTarget.value ) } autofocus/>
      </Field>
      <SheetFooter>
        <Button variant="primary" block type="submit" disabled={ saving }>{ __( 'Save' ) }</Button>
      </SheetFooter>
    </form>
  )
}

/**
 * Who a task is for. It is only offered to someone who can give tasks to others, and starts as themselves.
 */
function AssignTo ( { ctx, value, onChange } ) {
  const { __ } = ctx.i18n
  const { data } = useFetch( () => ctx.api.get( 'mobile/task-assignees' ), [] )
  const people = ( data && data.items ) || []

  if ( people.length < 2 ) {
    return null
  }

  return (
    <Field label={ __( 'Assign to' ) }>
      <Select value={ String( value ) } onChange={ e => onChange( Number( e.currentTarget.value ) ) } options={ people.map( p => ( { value: String( p.id ), label: p.id === ctx.user.id ? __( 'Me' ) : p.name } ) ) }/>
    </Field>
  )
}

/**
 * The "New task" sheet, attached to the contact it is given, or to one chosen in it.
 */
export function NewTaskSheet ( { ctx, close, contact: given, onCreated } ) {
  const { __ } = ctx.i18n
  const [ chosen, setChosen ] = useState( null )
  const contact = given || chosen
  const [ content, setContent ] = useState( '' )
  const [ due, setDue ] = useState( tomorrowMorning() )
  const [ assignee, setAssignee ] = useState( ctx.user.id )
  const [ saving, setSaving ] = useState( false )

  const save = async e => {
    e.preventDefault()

    if ( ! content.trim() ) {
      return
    }

    setSaving( true )

    // the first line is the task's title on the desktop, and the rest is what it says
    const { summary, content: details } = splitSummary( content )

    const data = {
      summary,
      // tasks are HTML, the desktop writes them in the editor, so the line breaks have to be markup
      content: plainToHtml( details ),
      type: 'task',
      user_id: assignee,
      due_date: toApiDate( due ),
    }

    if ( contact ) {
      data.object_id = contact.ID
      data.object_type = 'contact'
    }

    try {
      await ctx.api.post( 'tasks', { data } )
      ctx.toast( __( 'Task added' ) )
      ctx.emit( 'task:created', { contact: contact ? contact.ID : 0 } )
      onCreated && onCreated()
      close()
    } catch ( err ) {
      setSaving( false )
      ctx.toast( err.message, 'error' )
    }
  }

  return (
    <form onSubmit={ save }>
      <Field label={ __( 'What needs doing?' ) }>
        <Textarea value={ content } onInput={ e => setContent( e.currentTarget.value ) } required autofocus/>
      </Field>
      <Field label={ __( 'Due' ) }>
        <Input type="datetime-local" value={ due } onInput={ e => setDue( e.currentTarget.value ) }/>
      </Field>
      { ctx.can( 'edit_others_tasks' ) ? <AssignTo ctx={ ctx } value={ assignee } onChange={ setAssignee }/> : null }
      { given ? null : <ContactField ctx={ ctx } value={ chosen } onChange={ setChosen }/> }
      <SheetFooter>
        <Button variant="primary" block type="submit" disabled={ saving }>{ __( 'Add task' ) }</Button>
      </SheetFooter>
    </form>
  )
}
