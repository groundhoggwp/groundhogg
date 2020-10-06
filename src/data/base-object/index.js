/**
 * External dependencies
 */

import { registerStore } from '@wordpress/data'

/**
 * Internal dependencies
 */
import * as selectors from './selectors'
import * as actions from './actions'
import controls from '../controls'
import reducer from './reducer'
import * as resolvers from './resolvers'
import { NAMESPACE } from '../constants'

export function registerBaseObjectStore (endpoint) {

  resolvers.getEndpoint = (endpoint = storeName) => endpoint

  const storeName = NAMESPACE + '/' + endpoint
  return registerStore(storeName, {
    reducer,
    actions,
    selectors,
    controls,
    resolvers,
  })

}

export function getStoreName (ghEndpoint) {
  return NAMESPACE + '/' + ghEndpoint
}