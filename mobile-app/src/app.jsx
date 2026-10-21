// The shell: header, routed content, tab bar, create menu, and the sheet and toast hosts.

import { useEffect, useState, useCallback, useReducer } from 'preact/hooks'
import { matchRoute, normalizePath, firstSegment, parseHash, toHash } from './core/router.js'
import { RuntimeContext, useRuntime } from './runtime.jsx'
import { ErrorBoundary } from './contribution.jsx'
import { Icon, Sheet, Toast, Card, Skeleton, SkeletonList, Empty, List, ListItem, Button } from './ui/index.jsx'

const TAB_SLOTS = 5

function useHashRoute () {
  const [ route, setRoute ] = useState( () => parseHash( window.location.hash ) )

  useEffect( () => {
    const onChange = () => {
      setRoute( parseHash( window.location.hash ) )
      window.scrollTo( 0, 0 )
    }

    window.addEventListener( 'hashchange', onChange )
    return () => window.removeEventListener( 'hashchange', onChange )
  }, [] )

  return route
}

/**
 * Nav items for the tab bar and the More screen. Tab placement is a request: the bar holds five slots
 * including the create button and More, so the lowest priorities get tabs and the rest go to More.
 */
export function layoutNav ( items, hasCreate ) {
  const requested = items.filter( i => i.placement === 'tab' )
  const slots = TAB_SLOTS - 1 - ( hasCreate ? 1 : 0 )

  return {
    tabs: requested.slice( 0, slots ),
    more: [ ...requested.slice( slots ), ...items.filter( i => i.placement !== 'tab' ) ],
  }
}

function isActive ( item, path ) {
  const own = normalizePath( item.route )
  return own === '/' ? path === '/' : firstSegment( own ) === firstSegment( path )
}

function TabBar ( { tabs, hasCreate, hasMore, path, onCreate } ) {
  const { ctxFor } = useRuntime()
  const { __ } = ctxFor( 'core' ).i18n

  const items = tabs.map( item => (
    <a class={ `ghm-tab ${ isActive( item, path ) ? 'is-active' : '' }` } href={ toHash( item.route ) } aria-current={ isActive( item, path ) ? 'page' : undefined }>
      <Icon name={ item.icon }/>{ item.label }
    </a>
  ) )

  if ( hasCreate ) {
    const create = (
      <button type="button" class="ghm-tab ghm-tab-create" onClick={ onCreate } aria-label={ __( 'Create' ) }>
        <span><Icon name="plus"/></span>
      </button>
    )

    // the create button sits in the middle
    items.splice( Math.ceil( items.length / 2 ), 0, create )
  }

  if ( hasMore ) {
    items.push(
      <a class={ `ghm-tab ${ path === '/more' ? 'is-active' : '' }` } href="#/more"><Icon name="menu"/>{ __( 'More' ) }</a>
    )
  }

  return <nav class="ghm-tabs" aria-label={ __( 'Main' ) }>{ items }</nav>
}

function MoreView ( { moreItems } ) {
  const { manifest, ctxFor } = useRuntime()
  const { __ } = ctxFor( 'core' ).i18n

  return (
    <>
      { moreItems.length ? (
        <div class="ghm-card"><List>
          { moreItems.map( item => (
            <ListItem leading={ <Icon name={ item.icon }/> } title={ item.label } href={ toHash( item.route ) } trailing={ <Icon name="chevron"/> }/>
          ) ) }
        </List></div>
      ) : null }
      <div class="ghm-card"><List>
        <ListItem leading={ <Icon name="external"/> } title={ __( 'Open the full site' ) } subtitle={ __( 'Settings, flows and the email editor live on desktop.' ) } href={ manifest.app.adminUrl }/>
        <ListItem leading={ <Icon name="logout"/> } title={ __( 'Log out' ) } subtitle={ manifest.user.name } href={ manifest.app.logoutUrl }/>
      </List></div>
    </>
  )
}

export function App ( { runtime } ) {
  const route = useHashRoute()
  const { registry, loader, ctxFor } = runtime
  const { __ } = ctxFor( 'core' ).i18n

  // re-render when a lazily loaded feature registers
  const [ , bump ] = useReducer( n => n + 1, 0 )
  useEffect( () => registry.subscribe( bump ), [ registry ] )

  const [ sheet, setSheet ] = useState( null )
  const [ toast, setToast ] = useState( null )
  const [ failed, setFailed ] = useState( {} )

  // the shell hooks features reach through ctx
  useEffect( () => {
    let timer

    runtime.shell.toast = ( message, type ) => {
      setToast( { message, type } )
      clearTimeout( timer )
      timer = setTimeout( () => setToast( null ), 2800 )
    }
    runtime.shell.sheet = ( Component, props ) => setSheet( { Component, props } )

    return () => clearTimeout( timer )
  }, [] )

  const closeSheet = useCallback( () => setSheet( null ), [] )

  const nav = registry.contributions( 'nav' )
  const creates = registry.contributions( 'quick_create' )
  const { tabs, more } = layoutNav( nav, creates.length > 0 )

  const match = matchRoute( registry.routes(), route.path )
  const owner = match ? null : registry.featureForPath( route.path )

  // a deep link into an add-on's area loads its bundle first
  useEffect( () => {
    if ( ! match && owner && ! failed[ owner ] ) {
      loader.ensure( owner ).then( ok => ok || setFailed( f => ( { ...f, [ owner ]: true } ) ) )
    }
  }, [ route.path, owner, !! match ] )

  const atRoot = nav.some( i => normalizePath( i.route ) === route.path ) || route.path === '/more'
  const navItem = nav.find( i => normalizePath( i.route ) === route.path )
  const title = ( match && match.route.title ) || ( navItem && navItem.label ) || ( route.path === '/more' ? __( 'More' ) : runtime.manifest.app.name )

  const goBack = () => {
    if ( window.history.length > 1 ) {
      window.history.back()
    } else {
      window.location.hash = '#/'
    }
  }

  const openCreate = () => setSheet( {
    Component: CreateMenu,
    props: { title: __( 'Create' ), items: creates },
  } )

  let content

  if ( match ) {
    const Page = match.route.component
    content = (
      <ErrorBoundary label={ title } key={ route.path }>
        <Page ctx={ ctxFor( match.route.feature ) } params={ match.params } query={ route.query }/>
      </ErrorBoundary>
    )
  } else if ( route.path === '/more' ) {
    content = <MoreView moreItems={ more }/>
  } else if ( owner && ! failed[ owner ] ) {
    content = <Card><SkeletonList rows={ 4 }/></Card>
  } else {
    content = <Empty>{ __( 'There is nothing here.' ) } <a href="#/" style="color:var(--ghm-accent)">{ __( 'Go home' ) }</a></Empty>
  }

  return (
    <RuntimeContext.Provider value={ runtime }>
      <div class="ghm-app">
        <header class="ghm-head">
          <div class="ghm-head-side">
            { atRoot ? null : <Button class="icon" icon="back" aria-label={ __( 'Back' ) } onClick={ goBack }/> }
          </div>
          <h1>{ title }</h1>
          <div class="ghm-head-side"/>
        </header>
        <main class="ghm-main">{ content }</main>
        <TabBar tabs={ tabs } hasCreate={ creates.length > 0 } hasMore={ true } path={ route.path } onCreate={ openCreate }/>
        { sheet ? (
          <Sheet title={ sheet.props && sheet.props.title } onClose={ closeSheet }>
            <ErrorBoundary label={ __( 'Sheet' ) }>
              <sheet.Component { ...sheet.props } close={ closeSheet } ctx={ ctxFor( ( sheet.props && sheet.props.feature ) || 'core' ) }/>
            </ErrorBoundary>
          </Sheet>
        ) : null }
        { toast ? <Toast { ...toast }/> : null }
      </div>
    </RuntimeContext.Provider>
  )
}

/**
 * The create button's menu. Picking an item opens that item's sheet, loading the feature's bundle first if needed.
 */
function CreateMenu ( { items, close, ctx } ) {
  const { registry, loader } = useRuntime()
  const [ busy, setBusy ] = useState( '' )

  const pick = async item => {
    setBusy( item.id )
    await loader.ensure( item.feature )
    const Impl = registry.implementation( item.feature, 'quick_create', item.id )

    if ( ! Impl ) {
      setBusy( '' )
      ctx.toast( ctx.i18n.__( 'That is not available right now.' ), 'error' )
      return
    }

    // replace this menu with the item's own sheet
    ctx.sheet( Impl, { title: item.label, feature: item.feature } )
  }

  return (
    <List>
      { items.map( item => (
        <ListItem leading={ <Icon name={ item.icon }/> } title={ item.label } onClick={ () => pick( item ) } trailing={ busy === item.id ? <Skeleton round w={ 18 } h={ 18 }/> : null }/>
      ) ) }
    </List>
  )
}

