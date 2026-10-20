import test from 'node:test'
import assert from 'node:assert/strict'
import { readdirSync, readFileSync, statSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

// The app is used on white labeled sites, so what people read in it must not name the brand. The app's own name
// comes from the server (the white label name), and generic words do the rest.

const src = path.join( path.dirname( fileURLToPath( import.meta.url ) ), '../src' )

const files = dir => readdirSync( dir ).flatMap( name => {
  const full = path.join( dir, name )
  return statSync( full ).isDirectory() ? files( full ) : /\.(jsx?|css)$/.test( name ) ? [ full ] : []
} )

const brand = /groundhogg/i

test( 'no translated string in the app names the brand', () => {
  const offenders = []

  for ( const file of files( src ) ) {
    const text = readFileSync( file, 'utf8' )

    for ( const m of text.matchAll( /\b(?:__|_x|_n)\(\s*(['"`])((?:\.|(?!\1).)*)\1/g ) ) {
      if ( brand.test( m[ 2 ] ) ) {
        offenders.push( `${ path.relative( src, file ) }: ${ m[ 2 ] }` )
      }
    }
  }

  assert.deepEqual( offenders, [] )
})

test( 'no text written straight into the markup names the brand', () => {
  const offenders = []

  for ( const file of files( src ).filter( f => f.endsWith( '.jsx' ) ) ) {
    const text = readFileSync( file, 'utf8' )

    for ( const m of text.matchAll( />([^<>{}]*)</g ) ) {
      if ( brand.test( m[ 1 ] ) ) {
        offenders.push( `${ path.relative( src, file ) }: ${ m[ 1 ].trim() }` )
      }
    }
  }

  assert.deepEqual( offenders, [] )
})
