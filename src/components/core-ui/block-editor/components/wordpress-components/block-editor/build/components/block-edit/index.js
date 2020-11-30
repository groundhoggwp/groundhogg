"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockEdit;
Object.defineProperty(exports, "useBlockEditContext", {
  enumerable: true,
  get: function get() {
    return _context.useBlockEditContext;
  }
});

var _element = require("@wordpress/element");

var _edit = _interopRequireDefault(require("./edit"));

var _context = require("./context");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockEdit(props) {
  var name = props.name,
      isSelected = props.isSelected,
      clientId = props.clientId,
      onFocus = props.onFocus,
      onCaretVerticalPositionChange = props.onCaretVerticalPositionChange;
  var context = {
    name: name,
    isSelected: isSelected,
    clientId: clientId,
    onFocus: onFocus,
    onCaretVerticalPositionChange: onCaretVerticalPositionChange
  };
  return (0, _element.createElement)(_context.BlockEditContextProvider // It is important to return the same object if props haven't
  // changed to avoid  unnecessary rerenders.
  // See https://reactjs.org/docs/context.html#caveats.
  , {
    value: (0, _element.useMemo)(function () {
      return context;
    }, Object.values(context))
  }, (0, _element.createElement)(_edit.default, props));
}
//# sourceMappingURL=index.js.map