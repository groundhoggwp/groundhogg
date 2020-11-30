"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _compose = require("@wordpress/compose");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function ResponsiveWrapper(_ref) {
  var naturalWidth = _ref.naturalWidth,
      naturalHeight = _ref.naturalHeight,
      children = _ref.children,
      _ref$isInline = _ref.isInline,
      isInline = _ref$isInline === void 0 ? false : _ref$isInline;

  var _useResizeObserver = (0, _compose.useResizeObserver)(),
      _useResizeObserver2 = (0, _slicedToArray2.default)(_useResizeObserver, 2),
      containerResizeListener = _useResizeObserver2[0],
      containerWidth = _useResizeObserver2[1].width;

  if (_element.Children.count(children) !== 1) {
    return null;
  }

  var imageStyle = {
    paddingBottom: naturalWidth < containerWidth ? naturalHeight : naturalHeight / naturalWidth * 100 + '%'
  };
  var TagName = isInline ? 'span' : 'div';
  return (0, _element.createElement)(TagName, {
    className: "components-responsive-wrapper"
  }, containerResizeListener, (0, _element.createElement)(TagName, {
    style: imageStyle
  }), (0, _element.cloneElement)(children, {
    className: (0, _classnames.default)('components-responsive-wrapper__content', children.props.className)
  }));
}

var _default = ResponsiveWrapper;
exports.default = _default;
//# sourceMappingURL=index.js.map