"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.CardDivider = CardDivider;
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _cardStyles = require("./styles/card-styles");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function CardDivider(props) {
  var className = props.className,
      additionalProps = (0, _objectWithoutProperties2.default)(props, ["className"]);
  var classes = (0, _classnames.default)('components-card__divider', className);
  return (0, _element.createElement)(_cardStyles.DividerUI, (0, _extends2.default)({}, additionalProps, {
    children: null,
    className: classes,
    role: "separator"
  }));
}

var _default = CardDivider;
exports.default = _default;
//# sourceMappingURL=divider.js.map