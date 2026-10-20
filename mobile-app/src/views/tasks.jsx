import { useState } from 'preact/hooks'
import { Avatar, Button, Card, Empty, ErrorMessage, Field, Icon, Input, List, ListItem, Pill, SheetFooter, SkeletonList, Textarea } from '../ui/index.jsx'
import { plainText, useFetch, toApiDate, tomorrowMorning } from '../util.js'

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
 */
export function useTaskList ( ctx, query, deps ) {
  const { data, error, loading, reload } = useFetch( () => ctx.api.get( 'tasks', query ), deps )
  const [ done, setDone ] = useState( {} )

  const complete = task => {
    setDone( d => ( { ...d, [ task.ID ]: true } ) )

    ctx.api.patch( `tasks/${ task.ID }/complete` ).then(
      () => ctx.toast( ctx.i18n.__( 'Task completed' ) ),
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
 * The "New task" sheet, optionally attached to a contact.
 */
export function NewTaskSheet ( { ctx, close, contact, onCreated } ) {
  const { __ } = ctx.i18n
  const [ content, setContent ] = useState( '' )
  const [ due, setDue ] = useState( tomorrowMorning() )
  const [ saving, setSaving ] = useState( false )

  const save = async e => {
    e.preventDefault()

    if ( ! content.trim() ) {
      return
    }

    setSaving( true )

    const data = {
      content: content.trim(),
      type: 'task',
      user_id: ctx.user.id,
      due_date: toApiDate( due ),
    }

    if ( contact ) {
      data.object_id = contact.ID
      data.object_type = 'contact'
    }

    try {
      await ctx.api.post( 'tasks', { data } )
      ctx.toast( __( 'Task added' ) )
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
      <SheetFooter>
        <Button variant="primary" block type="submit" disabled={ saving }>{ __( 'Add task' ) }</Button>
      </SheetFooter>
    </form>
  )
}
