"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.useBlockNavigationContext = exports.BlockNavigationContext = void 0;

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */
var BlockNavigationContext = (0, _element.createContext)({
  __experimentalFeatures: false
});
exports.BlockNavigationContext = BlockNavigationContext;

var useBlockNavigationContext = function useBlockNavigationContext() {
  return (0, _element.useContext)(BlockNavigationContext);
};

exports.useBlockNavigationContext = useBlockNavigationContext;
//# sourceMappingURL=context.js.map