"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.useCardContext = exports.CardContext = void 0;

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */
var CardContext = (0, _element.createContext)({});
exports.CardContext = CardContext;

var useCardContext = function useCardContext() {
  return (0, _element.useContext)(CardContext);
};

exports.useCardContext = useCardContext;
//# sourceMappingURL=context.js.map