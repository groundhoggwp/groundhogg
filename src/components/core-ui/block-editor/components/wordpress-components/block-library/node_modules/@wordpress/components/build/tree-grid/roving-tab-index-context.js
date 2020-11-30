"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.RovingTabIndexProvider = exports.useRovingTabIndexContext = void 0;

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */
var RovingTabIndexContext = (0, _element.createContext)();

var useRovingTabIndexContext = function useRovingTabIndexContext() {
  return (0, _element.useContext)(RovingTabIndexContext);
};

exports.useRovingTabIndexContext = useRovingTabIndexContext;
var RovingTabIndexProvider = RovingTabIndexContext.Provider;
exports.RovingTabIndexProvider = RovingTabIndexProvider;
//# sourceMappingURL=roving-tab-index-context.js.map