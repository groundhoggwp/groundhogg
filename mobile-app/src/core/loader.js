// Loads a feature's bundle the first time something it contributes is about to render.

function withVersion ( src, version ) {
  if ( ! version ) {
    return src
  }

  return src + ( src.includes( '?' ) ? '&' : '?' ) + 'ver=' + encodeURIComponent( version )
}

export function createLoader ( registry, doc = document ) {

  const pending = new Map()

  const addStyle = ( style ) => {
    if ( ! style || ! style.src ) {
      return
    }

    const link = doc.createElement( 'link' )
    link.rel = 'stylesheet'
    link.href = withVersion( style.src, style.version )
    doc.head.appendChild( link )
  }

  return {

    /**
     * @param {string} featureId
     * @returns {Promise<boolean>} whether the feature's implementation is registered afterwards
     */
    ensure ( featureId ) {

      if ( registry.has( featureId ) ) {
        return Promise.resolve( true )
      }

      const feature = registry.feature( featureId )

      // declarative only, or its components are already in the core bundle
      if ( ! feature || ! feature.script ) {
        return Promise.resolve( false )
      }

      if ( ! pending.has( featureId ) ) {

        addStyle( feature.style )

        pending.set( featureId, new Promise( ( resolve ) => {
          const script = doc.createElement( 'script' )
          script.src = withVersion( feature.script.src, feature.script.version )
          script.async = true
          script.onload = () => resolve( registry.has( featureId ) )
          script.onerror = () => {
            // allow another try, a flaky connection shouldn't poison the feature until a reload
            pending.delete( featureId )
            resolve( false )
          }
          doc.head.appendChild( script )
        } ) )
      }

      return pending.get( featureId )
    },
  }
}
