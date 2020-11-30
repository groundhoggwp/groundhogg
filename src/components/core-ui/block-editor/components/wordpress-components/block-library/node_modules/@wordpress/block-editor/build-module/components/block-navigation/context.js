/**
 * WordPress dependencies
 */
import { createContext, useContext } from '@wordpress/element';
export var BlockNavigationContext = createContext({
  __experimentalFeatures: false
});
export var useBlockNavigationContext = function useBlockNavigationContext() {
  return useContext(BlockNavigationContext);
};
//# sourceMappingURL=context.js.map