import { render } from 'preact'
import { createRuntime } from './runtime.jsx'
import { registerCore } from './features/core.jsx'
import { App } from './app.jsx'

function boot () {
  const el = document.getElementById( 'ghm-manifest' )
  const root = document.getElementById( 'ghm-root' )

  if ( ! el || ! root ) {
    return
  }

  const manifest = JSON.parse( el.textContent )
  const runtime = createRuntime( manifest )

  registerCore()

  render( <App runtime={ runtime }/>, root )

  if ( 'serviceWorker' in navigator ) {
    // scope is the app's own path, because the worker is served from under it
    navigator.serviceWorker.register( manifest.app.url + 'sw.js', { scope: manifest.app.url } ).catch( () => {
      // the app works without it, there is just no instant launch
    } )
  }
}

boot()
