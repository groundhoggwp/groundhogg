/**
 * Internal dependencies
 */
import {
  registerBaseObjectStore,
  getStoreName
} from '../base-object'
import BaseActions from './actions'

const STORE_NAME = 'emails'
const actions = new BaseActions(STORE_NAME)

registerBaseObjectStore(STORE_NAME, {
  actions: actions
})

export const EMAILS_STORE_NAME = getStoreName(STORE_NAME)
