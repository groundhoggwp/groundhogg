"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _header = _interopRequireDefault(require("./header"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function Panel(_ref) {
  var header = _ref.header,
      className = _ref.className,
      children = _ref.children;
  var classNames = (0, _classnames.default)(className, 'components-panel');
  return (0, _element.createElement)("div", {
    className: classNames
  }, header && (0, _element.createElement)(_header.default, {
    label: header
  }), children);
}

var _default = Panel;
exports.default = _default;
//# sourceMappingURL=index.js.map