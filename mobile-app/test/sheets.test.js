import test from 'node:test'
import assert from 'node:assert/strict'
import { readdirSync, readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

// The main button of a sheet (Save, Send, Add) has to stay on screen however long the sheet is, so a first-time user
// never has to look for it. That is what SheetFooter does, and this fails when a sheet forgets to use it.

const views = path.join( path.dirname( fileURLToPath( import.meta.url ) ), '../src/views' )

test( 'every primary full-width button in a sheet is inside a SheetFooter', () => {
  const offenders = []

  for ( const name of readdirSync( views ).filter( n => n.endsWith( '.jsx' ) ) ) {
    const lines = readFileSync( path.join( views, name ), 'utf8' ).split( '\n' )

    lines.forEach( ( line, i ) => {
      if ( ! /variant="primary"/.test( line ) || ! /\bblock\b/.test( line ) ) {
        return
      }

      // the footer opens on the line before the button, or on the same line
      const nearby = lines.slice( Math.max( 0, i - 2 ), i + 1 ).join( '\n' )

      if ( ! /<SheetFooter/.test( nearby ) ) {
        offenders.push( `${ name }:${ i + 1 }  ${ line.trim().slice( 0, 80 ) }` )
      }
    } )
  }

  assert.deepEqual( offenders, [] )
})

test( 'SheetFooter is part of the kit add-ons get as ctx.ui', () => {
  const kit = readFileSync( path.join( views, '../ui/index.jsx' ), 'utf8' )
  assert.match( kit, /export const SheetFooter/ )
})
