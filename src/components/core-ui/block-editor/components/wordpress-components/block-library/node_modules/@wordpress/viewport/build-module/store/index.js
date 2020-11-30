/**
 * WordPress dependencies
 */
import { registerStore } from '@wordpress/data';
/**
 * Internal dependencies
 */

import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
export default registerStore('core/viewport', {
  reducer: reducer,
  actions: actions,
  selectors: selectors
});
//# sourceMappingURL=index.js.map