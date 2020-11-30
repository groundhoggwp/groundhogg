"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.CardMedia = CardMedia;
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
function CardMedia(props) {
  var className = props.className,
      additionalProps = (0, _objectWithoutProperties2.default)(props, ["className"]);
  var classes = (0, _classnames.default)('components-card__media', className);
  return (0, _element.createElement)(_cardStyles.MediaUI, (0, _extends2.default)({}, additionalProps, {
    className: classes
  }));
}

var _default = CardMedia;
exports.default = _default;
//# sourceMappingURL=media.js.map