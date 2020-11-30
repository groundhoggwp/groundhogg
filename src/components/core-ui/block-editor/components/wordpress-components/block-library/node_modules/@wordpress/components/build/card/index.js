"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Card = Card;
exports.default = exports.defaultProps = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _context = require("./context");

var _cardStyles = require("./styles/card-styles");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
var defaultProps = {
  isBorderless: false,
  isElevated: false,
  size: 'medium'
};
exports.defaultProps = defaultProps;

function Card(props) {
  var className = props.className,
      isBorderless = props.isBorderless,
      isElevated = props.isElevated,
      size = props.size,
      additionalProps = (0, _objectWithoutProperties2.default)(props, ["className", "isBorderless", "isElevated", "size"]);
  var Provider = _context.CardContext.Provider;
  var contextProps = {
    isBorderless: isBorderless,
    isElevated: isElevated,
    size: size
  };
  var classes = (0, _classnames.default)('components-card', isBorderless && 'is-borderless', isElevated && 'is-elevated', size && "is-size-".concat(size), className);
  return (0, _element.createElement)(Provider, {
    value: contextProps
  }, (0, _element.createElement)(_cardStyles.CardUI, (0, _extends2.default)({}, additionalProps, {
    className: classes
  })));
}

Card.defaultProps = defaultProps;
var _default = Card;
exports.default = _default;
//# sourceMappingURL=index.js.map