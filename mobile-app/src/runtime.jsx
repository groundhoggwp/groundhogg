// Wires the manifest, API client, registry and loader together and builds the `ctx` every component receives.

import { createContext } from 'preact'
import { useContext } from 'preact/hooks'
import * as preact from 'preact'
import * as hooks from 'preact/hooks'

import { createApi } from './core/api.js'
import { createRegistry, API_VERSION } from './core/registry.js'
import { createLoader } from './core/loader.js'
import { createI18n } from './core/i18n.js'
import { parseHash, toHash } from './core/router.js'
import * as ui from './ui/index.jsx'

export const RuntimeContext = createContext( null )
export const useRuntime = () => useContext( RuntimeContext )

/**
 * @param {object} manifest the per-user manifest PHP inlined into the page
 * @param {{toast: Function, sheet: Function}} shell hooks the shell fills in once it has mounted
 */
export function createRuntime ( manifest, shell = {} ) {

  const api = createApi( manifest.rest )
  const registry = createRegistry( manifest )
  const loader = createLoader( registry )

  const caps = manifest.caps || {}
  const translations = manifest.i18n || {}
  const ctxCache = {}

  /**
   * One ctx per feature, because only i18n differs and that depends on the feature's text domain.
   */
  const ctxFor = ( featureId = 'core' ) => {

    if ( ! ctxCache[ featureId ] ) {
      const feature = registry.feature( featureId ) || {}
      const domain = ( feature.script && feature.script.domain ) || 'groundhogg'

      ctxCache[ featureId ] = {
        api,
        ui,
        preact: { ...preact, ...hooks },
        i18n: createI18n( translations, domain ),
        user: manifest.user,
        app: manifest.app,
        can: cap => !! caps[ cap ],
        get route () { return parseHash( window.location.hash ) },
        navigate: ( path, query ) => {
          window.location.hash = toHash( path, query )
        },
        toast: ( message, type ) => shell.toast && shell.toast( message, type ),
        sheet: ( Component, props ) => shell.sheet && shell.sheet( Component, props ),
        confirm: message => Promise.resolve( window.confirm( message ) ),
      }
    }

    return ctxCache[ featureId ]
  }

  const runtime = { manifest, api, registry, loader, ctxFor, shell }

  // what feature bundles see. Preact is exposed so they can use it as an external instead of bundling their own.
  window.GroundhoggMobile = {
    apiVersion: API_VERSION,
    register: ( id, impl ) => registry.register( id, impl ),
    ui,
    preact: { ...preact, ...hooks },
  }

  return runtime
}
