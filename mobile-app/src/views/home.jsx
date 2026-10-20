import { Card, Empty, ErrorMessage, List, SkeletonList } from '../ui/index.jsx'
import { Contribution } from '../contribution.jsx'
import { useRuntime } from '../runtime.jsx'
import { ContactRow } from './contacts.jsx'
import { TaskRow, useTaskList, myTasksQuery } from './tasks.jsx'
import { useFetch } from '../util.js'

/**
 * Home is "my day": whatever features contribute as cards, lowest priority first. Each card loads its
 * own data, and renders nothing when it has none.
 */
export function HomeView ( { ctx } ) {
  const { registry } = useRuntime()
  const cards = registry.contributions( 'home_cards' )

  return (
    <>
      { cards.map( card => (
        <Contribution point="home_cards" feature={ card.feature } id={ card.id } label={ card.label } key={ `${ card.feature }/${ card.id }` }/>
      ) ) }
    </>
  )
}

export function MyTasksCard ( { ctx } ) {
  const { __ } = ctx.i18n
  const { tasks, error, loading, complete } = useTaskList( ctx, { ...myTasksQuery( ctx ), limit: 5 }, [] )

  const seeAll = <a href="#/tasks" style="color:var(--ghm-accent);text-transform:none;letter-spacing:0">{ __( 'See all' ) }</a>

  return (
    <Card title={ __( 'My tasks' ) } action={ seeAll }>
      <ErrorMessage error={ error }/>
      { loading && ! tasks.length ? <SkeletonList variant="check" rows={ 3 }/> : null }
      { ! loading && ! error && ! tasks.length ? <Empty>{ __( 'Nothing to do. Enjoy it.' ) }</Empty> : null }
      { tasks.length ? <List>{ tasks.map( t => <TaskRow task={ t } ctx={ ctx } onComplete={ complete } showContact key={ t.ID }/> ) }</List> : null }
    </Card>
  )
}

export function RecentContactsCard ( { ctx } ) {
  const { __ } = ctx.i18n
  const { data, error, loading } = useFetch( () => ctx.api.get( 'contacts', { limit: 5, orderby: 'ID', order: 'DESC' } ), [] )
  const items = ( data && data.items ) || []

  const seeAll = <a href="#/contacts" style="color:var(--ghm-accent);text-transform:none;letter-spacing:0">{ __( 'See all' ) }</a>

  // an empty card would just be noise
  if ( ! loading && ! error && ! items.length ) {
    return null
  }

  return (
    <Card title={ __( 'Recently added' ) } action={ seeAll }>
      <ErrorMessage error={ error }/>
      { loading && ! items.length ? <SkeletonList variant="avatar" rows={ 3 }/> : null }
      { items.length ? <List>{ items.map( c => <ContactRow contact={ c } key={ c.ID }/> ) }</List> : null }
    </Card>
  )
}
