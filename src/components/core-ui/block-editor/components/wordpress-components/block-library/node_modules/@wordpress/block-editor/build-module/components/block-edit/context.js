/**
 * External dependencies
 */
import { noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { createContext, useContext } from '@wordpress/element';
var Context = createContext({
  name: '',
  isSelected: false,
  focusedElement: null,
  setFocusedElement: noop,
  clientId: null
});
var Provider = Context.Provider;
export { Provider as BlockEditContextProvider };
/**
 * A hook that returns the block edit context.
 *
 * @return {Object} Block edit context
 */

export function useBlockEditContext() {
  return useContext(Context);
}
//# sourceMappingURL=context.js.map