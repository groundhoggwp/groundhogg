// The client half of the feature registry.
//
// The manifest (built per user by PHP) says which features exist and what they contribute. A feature's JS
// calls GroundhoggMobile.register( id, implementation ) to supply the components, keyed by the same ids.
// The manifest is the source of truth: registering an id it didn't list is ignored.

import { firstSegment } from './router.js'

export const API_VERSION = 1

// manifest extension point -> key on the implementation object
const IMPL_KEYS = {
  home_cards: 'homeCards',
  contact_panels: 'contactPanels',
  contact_actions: 'contactActions',
  quick_create: 'quickCreate',
  search_providers: 'searchProviders',
}

export function createRegistry ( manifest, { warn = ( ...a ) => console.warn( ...a ) } = {} ) {

  const features = manifest.features || {}
  const impls = new Map()
  const listeners = new Set()
  const emit = () => listeners.forEach( fn => fn() )

  return {

    subscribe ( fn ) {
      listeners.add( fn )
      return () => listeners.delete( fn )
    },

    /**
     * @returns {boolean} whether the implementation was accepted
     */
    register ( id, impl ) {

      if ( ! features[ id ] ) {
        warn( `Mobile app: "${ id }" is not an available feature, ignoring it.` )
        return false
      }

      if ( impls.has( id ) ) {
        warn( `Mobile app: "${ id }" is already registered.` )
        return false
      }

      if ( ( impl && impl.apiVersion || 1 ) > API_VERSION ) {
        warn( `Mobile app: "${ id }" needs a newer mobile API than this site has.` )
        return false
      }

      impls.set( id, impl || {} )
      emit()
      return true
    },

    has: id => impls.has( id ),

    feature: id => features[ id ] || null,

    /**
     * Every visible contribution to an extension point across features, lowest priority first.
     */
    contributions ( point ) {
      const out = []

      Object.keys( features ).forEach( featureId => {
        ( features[ featureId ][ point ] || [] ).forEach( c => out.push( { ...c, feature: featureId } ) )
      } )

      return out.sort( ( a, b ) => a.priority - b.priority || a.id.localeCompare( b.id ) )
    },

    /**
     * The component a feature supplied for one of its contributions, or null (not loaded yet, or declarative only).
     */
    implementation ( featureId, point, id ) {
      const impl = impls.get( featureId )
      const group = impl && impl[ IMPL_KEYS[ point ] ]
      return ( group && group[ id ] ) || null
    },

    /**
     * Routes from every registered implementation.
     */
    routes () {
      const out = []

      impls.forEach( ( impl, featureId ) => {
        Object.keys( impl.routes || {} ).forEach( path => {
          const def = impl.routes[ path ]
          out.push( {
            path,
            feature: featureId,
            component: typeof def === 'function' ? def : def.component,
            title: typeof def === 'function' ? '' : def.title || '',
          } )
        } )
      } )

      return out
    },

    /**
     * Which feature owns a path, from the first segment of the routes it declares in nav and search.
     * That lets the shell load a bundle the first time someone follows a deep link into its area.
     */
    featureForPath ( path ) {
      const segment = firstSegment( path )

      if ( ! segment ) {
        return null
      }

      for ( const featureId of Object.keys( features ) ) {
        const declared = [ ...( features[ featureId ].nav || [] ), ...( features[ featureId ].search_providers || [] ) ]

        if ( declared.some( c => c.route && firstSegment( c.route ) === segment ) ) {
          return featureId
        }
      }

      return null
    },
  }
}
