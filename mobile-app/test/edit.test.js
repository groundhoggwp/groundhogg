import test from 'node:test'
import assert from 'node:assert/strict'

import { initialValues, changes, isEmpty, formatAddress, EDIT_FIELDS } from '../src/core/fields.js'
import { resultingTags, tagPayload, hasTag, isUnchanged, fromApi } from '../src/core/tags.js'
import { countryName, countryOptions, COUNTRY_CODES } from '../src/core/countries.js'

const contact = {
  data: { first_name: 'Ann', last_name: 'Lee', email: 'ann@x.test' },
  meta: { mobile_phone: '555 1', city: 'Austin', region: 'TX', postal_zip: '78701', country: 'US', street_address_1: '1 Main St' },
}

// ---- fields ----------------------------------------------------------------------------------------------------

test( 'initialValues reads data and meta, and missing fields are empty strings', () => {
  const v = initialValues( contact )

  assert.equal( v.first_name, 'Ann' )
  assert.equal( v.mobile_phone, '555 1' )
  assert.equal( v.job_title, '' )
  assert.equal( Object.keys( v ).length, EDIT_FIELDS.length )
})

test( 'changes sends only what changed, split into data and meta', () => {
  const v = { ...initialValues( contact ), first_name: 'Anna', job_title: 'Buyer', city: 'Dallas' }

  assert.deepEqual( changes( contact, v ), { data: { first_name: 'Anna' }, meta: { job_title: 'Buyer', city: 'Dallas' } } )
})

test( 'changes ignores stray whitespace and reports nothing to save', () => {
  const v = { ...initialValues( contact ), first_name: '  Ann ', city: 'Austin ' }
  const c = changes( contact, v )

  assert.deepEqual( c, { data: {}, meta: {} } )
  assert.equal( isEmpty( c ), true )
})

test( 'changes can clear a field', () => {
  assert.deepEqual( changes( contact, { ...initialValues( contact ), mobile_phone: '' } ), { data: {}, meta: { mobile_phone: '' } } )
})

test( 'formatAddress joins what is there and skips what is blank', () => {
  assert.equal( formatAddress( contact, c => ( { US: 'United States' } )[ c ] ), '1 Main St, Austin, TX 78701, United States' )
  assert.equal( formatAddress( { meta: { city: 'Paris' } } ), 'Paris' )
  assert.equal( formatAddress( { meta: {} } ), '' )
  assert.equal( formatAddress( null ), '' )
})

// ---- tags ------------------------------------------------------------------------------------------------------

const current = [ { id: 1, name: 'Customer' }, { id: 2, name: 'Lead' } ]

test( 'tagPayload sends existing tags by id and new ones by name', () => {
  assert.deepEqual( tagPayload( current, [ { id: 9, name: 'VIP' }, { name: 'Brand new' } ], [ 2 ] ), { add_tags: [ 9, 'Brand new' ], remove_tags: [ 2 ] } )
})

test( 'tagPayload drops no-ops: adding what is there, removing what is not, adding twice', () => {
  const p = tagPayload( current, [ { id: 1, name: 'Customer' }, { name: 'lead' }, { name: 'New' }, { name: 'new' } ], [ 77 ] )

  assert.deepEqual( p, { add_tags: [ 'New' ], remove_tags: [] } )
  assert.equal( isUnchanged( tagPayload( current, [], [] ) ), true )
})

test( 'removing a tag and adding it back is no change', () => {
  assert.deepEqual( tagPayload( current, [ { id: 2, name: 'Lead' } ], [ 2 ] ), { add_tags: [], remove_tags: [] } )
})

test( 'resultingTags shows the contact as it will be', () => {
  assert.deepEqual( resultingTags( current, [ { id: 9, name: 'VIP' }, { name: 'customer' } ], [ 2 ] ).map( t => t.name ), [ 'Customer', 'VIP' ] )
})

test( 'hasTag matches by id or by name regardless of case', () => {
  assert.equal( hasTag( current, { id: 2, name: 'x' } ), true )
  assert.equal( hasTag( current, { name: 'CUSTOMER' } ), true )
  assert.equal( hasTag( current, { name: 'Other' } ), false )
})

test( 'fromApi reads the REST shape', () => {
  assert.deepEqual( fromApi( [ { ID: '4', data: { tag_name: 'Website Lead' } } ] ), [ { id: 4, name: 'Website Lead' } ] )
  assert.deepEqual( fromApi( undefined ), [] )
})

// ---- countries -------------------------------------------------------------------------------------------------

test( 'country names come from the browser and fall back to the code', () => {
  assert.equal( countryName( 'us', 'en' ), 'United States' )
  assert.equal( countryName( '', 'en' ), '' )
  assert.equal( countryName( 'ZZ', 'en' ).length > 0, true )
})

test( 'the country list has no duplicates and keeps a code it does not know', () => {
  assert.equal( new Set( COUNTRY_CODES ).size, COUNTRY_CODES.length )
  assert.ok( COUNTRY_CODES.length >= 245 )

  const opts = countryOptions( 'en', 'XK' )
  assert.ok( opts.some( o => o.value === 'XK' ) )
  assert.deepEqual( opts.map( o => o.label ), [ ...opts.map( o => o.label ) ].sort( ( a, b ) => a.localeCompare( b, 'en' ) ) )
  assert.equal( countryOptions( 'en', 'US' ).length, COUNTRY_CODES.length )
})
