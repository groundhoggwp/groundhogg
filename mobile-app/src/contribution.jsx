// Renders one contribution of a feature: loads the feature's bundle on demand, then renders the component
// it registered. One feature's failure shows a small card, it never takes the app down.

import { Component } from 'preact'
import { useEffect, useReducer } from 'preact/hooks'
import { useRuntime } from './runtime.jsx'
import { Loading } from './ui/index.jsx'

export class ErrorBoundary extends Component {
  state = { error: null }

  static getDerivedStateFromError ( error ) {
    return { error }
  }

  componentDidCatch ( error ) {
    console.error( 'Groundhogg mobile: a feature failed to render.', error )
  }

  render ( { label, children } ) {
    if ( this.state.error ) {
      return <div class="ghm-error" role="alert">{ label ? `${ label }: ` : '' }couldn't load.</div>
    }

    return children
  }
}

/**
 * Makes a component re-render when the registry changes, which is how a lazily loaded feature appears.
 */
export function useRegistryVersion () {
  const { registry } = useRuntime()
  const [ , bump ] = useReducer( n => n + 1, 0 )
  useEffect( () => registry.subscribe( bump ), [ registry ] )
}

/**
 * @param {{point: string, feature: string, id: string, label?: string}} props plus anything to pass down
 */
export function Contribution ( { point, feature, id, label, fallback = null, ...rest } ) {
  const { registry, loader, ctxFor } = useRuntime()
  useRegistryVersion()

  const Impl = registry.implementation( feature, point, id )

  useEffect( () => {
    if ( ! Impl ) {
      loader.ensure( feature )
    }
  }, [ Impl, feature ] )

  if ( ! Impl ) {
    // declarative contributions have nothing to load
    return registry.feature( feature ) && registry.feature( feature ).script && ! registry.has( feature ) ? <Loading/> : fallback
  }

  return (
    <ErrorBoundary label={ label }>
      <Impl ctx={ ctxFor( feature ) } { ...rest }/>
    </ErrorBoundary>
  )
}
