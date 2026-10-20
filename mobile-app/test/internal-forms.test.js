import test from 'node:test'
import assert from 'node:assert/strict'

import { isModernForm, formEntries, withContact, isUnsafeAttribute, UNSAFE_TAGS } from '../src/core/forms.js'

test( 'only current-style forms are offered', () => {
  assert.equal( isModernForm( '<form class="gh-form gh-form-v2 simple"></form>' ), true )
  assert.equal( isModernForm( '<form class="gh-form gh-form-v1"></form>' ), false )
  assert.equal( isModernForm( '<form class="my-gh-form-v2x"></form>' ), false )
  assert.equal( isModernForm( undefined ), false )
})

test( 'formEntries keeps the forms the app can show and reads their fields', () => {
  const items = [
    { ID: '56', name: 'Intake', rendered: '<form class="gh-form gh-form-v2"></form>' },
    { ID: '2', name: 'Old', rendered: '<form class="gh-form"></form>' },
    { ID: '3', name: 'No markup' },
    null,
  ]

  assert.deepEqual( formEntries( items ), [ { id: 56, name: 'Intake', html: '<form class="gh-form gh-form-v2"></form>' } ] )
  assert.deepEqual( formEntries( undefined ), [] )
})

test( 'withContact says who the submission is for', () => {
  const data = new FormData()
  data.append( 'first_name', 'Ann' )
  data.append( 'contact', '999' )

  withContact( data, 42 )

  assert.deepEqual( data.getAll( 'contact' ), [ '42' ] )
  assert.equal( data.get( 'first_name' ), 'Ann' )
})

test( 'scripts, styles and anything that runs code from an attribute are dropped from rendered forms', () => {
  for ( const tag of [ 'script', 'style', 'iframe', 'link' ] ) {
    assert.ok( UNSAFE_TAGS.includes( tag ), tag )
  }

  assert.equal( isUnsafeAttribute( 'onclick', 'x()' ), true )
  assert.equal( isUnsafeAttribute( 'ONERROR', 'x()' ), true )
  assert.equal( isUnsafeAttribute( 'href', 'javascript:alert(1)' ), true )
  assert.equal( isUnsafeAttribute( 'src', ' data:text/html,x' ), true )
  assert.equal( isUnsafeAttribute( 'formaction', 'JavaScript:x' ), true )

  assert.equal( isUnsafeAttribute( 'href', 'https://example.test/' ), false )
  assert.equal( isUnsafeAttribute( 'name', 'first_name' ), false )
  assert.equal( isUnsafeAttribute( 'class', 'gh-input' ), false )
  assert.equal( isUnsafeAttribute( 'value', 'javascript: is a language' ), false )
})

import { identitySelector, IDENTITY_FIELDS } from '../src/core/forms.js'

test( 'the email is locked in internal forms, because it decides which contact the server saves to', () => {
  assert.deepEqual( IDENTITY_FIELDS, [ 'email' ] )
  assert.equal( identitySelector(), '[name="email"]' )
})
