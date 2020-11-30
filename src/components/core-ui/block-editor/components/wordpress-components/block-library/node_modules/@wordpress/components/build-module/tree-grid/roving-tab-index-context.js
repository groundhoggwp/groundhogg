/**
 * WordPress dependencies
 */
import { createContext, useContext } from '@wordpress/element';
var RovingTabIndexContext = createContext();
export var useRovingTabIndexContext = function useRovingTabIndexContext() {
  return useContext(RovingTabIndexContext);
};
export var RovingTabIndexProvider = RovingTabIndexContext.Provider;
//# sourceMappingURL=roving-tab-index-context.js.map