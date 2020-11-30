"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.useBlockEditContext = useBlockEditContext;
exports.BlockEditContextProvider = void 0;

var _lodash = require("lodash");

var _element = require("@wordpress/element");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var Context = (0, _element.createContext)({
  name: '',
  isSelected: false,
  focusedElement: null,
  setFocusedElement: _lodash.noop,
  clientId: null
});
var Provider = Context.Provider;
exports.BlockEditContextProvider = Provider;

/**
 * A hook that returns the block edit context.
 *
 * @return {Object} Block edit context
 */
function useBlockEditContext() {
  return (0, _element.useContext)(Context);
}
//# sourceMappingURL=context.js.map