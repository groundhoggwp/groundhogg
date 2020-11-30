/**
 * WordPress dependencies
 */
import { createContext, useContext } from '@wordpress/element';
export var CardContext = createContext({});
export var useCardContext = function useCardContext() {
  return useContext(CardContext);
};
//# sourceMappingURL=context.js.map