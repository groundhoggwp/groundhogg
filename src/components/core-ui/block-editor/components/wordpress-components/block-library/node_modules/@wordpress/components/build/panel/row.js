"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

/**
 * External dependencies
 */
function PanelRow(_ref) {
  var className = _ref.className,
      children = _ref.children;
  var classes = (0, _classnames.default)('components-panel__row', className);
  return (0, _element.createElement)("div", {
    className: classes
  }, children);
}

var _default = PanelRow;
exports.default = _default;
//# sourceMappingURL=row.js.map