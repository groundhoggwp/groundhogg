import test from 'node:test'
import assert from 'node:assert/strict'

import { touch, drop, normalizeRecent, withoutRecents, MAX_RECENT } from '../src/core/recent.js'

test( 'touch puts the latest first and moves a repeat to the front without duplicating it', () => {
  let list = []
  for ( const id of [ 1, 2, 3 ] ) {
    list = touch( list, { id, name: `T${ id }` } )
  }

  assert.deepEqual( list.map( i => i.id ), [ 3, 2, 1 ] )

  list = touch( list, { id: 1, name: 'T1 renamed', sub: 'new sub' } )

  assert.deepEqual( list.map( i => i.id ), [ 1, 3, 2 ] )
  assert.deepEqual( list[ 0 ], { id: 1, name: 'T1 renamed', sub: 'new sub' } )
})

test( 'touch keeps only the most recent few, and does not change the list it was given', () => {
  const start = [ { id: 1, name: 'a', sub: '' } ]
  const copy = JSON.parse( JSON.stringify( start ) )

  let list = start
  for ( let id = 2; id <= MAX_RECENT + 4; id++ ) {
    list = touch( list, { id, name: `T${ id }` } )
  }

  assert.equal( list.length, MAX_RECENT )
  assert.equal( list[ 0 ].id, MAX_RECENT + 4 )
  assert.deepEqual( start, copy )
})

test( 'touch accepts an id that arrives as a string', () => {
  assert.deepEqual( touch( [ { id: 5, name: 'x', sub: '' } ], { id: '5', name: 'y' } ).map( i => [ i.id, i.name ] ), [ [ 5, 'y' ] ] )
})

test( 'drop removes one entry', () => {
  assert.deepEqual( drop( [ { id: 1 }, { id: 2 } ], '1' ), [ { id: 2 } ] )
  assert.deepEqual( drop( undefined, 1 ), [] )
})

test( 'normalizeRecent makes whatever the server sent safe to use', () => {
  const out = normalizeRecent( {
    form: [ { id: '4', name: 'Intake' }, { id: 'x', name: 'bad' }, null, { id: 0, name: 'zero' }, { id: 6 } ],
    tag: 'not a list',
    email_template: undefined,
  } )

  assert.deepEqual( out.form, [ { id: 4, name: 'Intake', sub: '' }, { id: 6, name: '', sub: '' } ] )
  assert.deepEqual( out.tag, [] )
  assert.deepEqual( out.email_template, [] )
  assert.deepEqual( normalizeRecent( null ), {} )
  assert.deepEqual( normalizeRecent( 'nope' ), {} )
})

test( 'withoutRecents shows something once, whichever way the id is named', () => {
  const recents = [ { id: 2 }, { id: 5 } ]

  assert.deepEqual( withoutRecents( [ { ID: '1' }, { ID: '2' }, { ID: 3 } ], recents ).map( i => i.ID ), [ '1', 3 ] )
  assert.deepEqual( withoutRecents( [ { id: 2 }, { id: 9 } ], recents ).map( i => i.id ), [ 9 ] )
  assert.deepEqual( withoutRecents( [ { id: 1 } ], [] ), [ { id: 1 } ] )
})
