import test from 'node:test'
import assert from 'node:assert/strict'

import { createApi, errorMessage } from '../src/core/api.js'

const res = ( status, body ) => ( { ok: status < 400, status, json: async () => body, text: async () => String( body ) } )

test( 'errorMessage prefers the listed errors over the generic one', () => {
  assert.equal( errorMessage( { message: 'Your submission has errors.', additional_errors: [ { message: 'Email is required.' }, { message: 'Phone is invalid.' } ] } ), 'Email is required. Phone is invalid.' )
  assert.equal( errorMessage( { message: 'Email address already in use.' } ), 'Email address already in use.' )
  assert.equal( errorMessage( { message: 'x', additional_errors: [] } ), 'x' )
  assert.equal( errorMessage( null ), '' )
})

test( 'api sends a FormData as it is, without a JSON content type', async () => {
  const calls = []
  const api = createApi( {
    root: 'https://x.test/wp-json/gh/v4/', nonce: 'abc123', nonceUrl: 'x',
    fetch: async ( url, init ) => { calls.push( init ); return res( 200, { status: 'success' } ) },
  } )

  const form = new FormData()
  form.append( 'contact', '5' )

  await api.postForm( 'forms/1/admin', form )

  assert.equal( calls[ 0 ].body, form )
  assert.equal( calls[ 0 ].headers[ 'Content-Type' ], undefined )
  assert.equal( calls[ 0 ].headers[ 'X-WP-Nonce' ], 'abc123' )
})

test( 'api shows the individual form errors', async () => {
  const api = createApi( {
    root: 'https://x.test/wp-json/gh/v4/', nonce: 'abc123', nonceUrl: 'x',
    fetch: async () => res( 400, { code: 'failed_to_submit', message: 'Your submission has errors.', additional_errors: [ { code: 'a', message: 'Email is required.' } ] } ),
  } )

  await assert.rejects( api.postForm( 'forms/1/admin', new FormData() ), e => e.status === 400 && e.message === 'Email is required.' )
})

test( 'json bodies still go as json', async () => {
  const calls = []
  const api = createApi( {
    root: 'https://x.test/wp-json/gh/v4/', nonce: 'abc123', nonceUrl: 'x',
    fetch: async ( url, init ) => { calls.push( init ); return res( 200, {} ) },
  } )

  await api.patch( 'contacts/1', { data: { first_name: 'A' } } )

  assert.equal( calls[ 0 ].headers[ 'Content-Type' ], 'application/json' )
  assert.equal( calls[ 0 ].body, '{"data":{"first_name":"A"}}' )
})
