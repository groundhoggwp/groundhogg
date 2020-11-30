"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _reResizable = require("re-resizable");

var _resizeTooltip = _interopRequireDefault(require("./resize-tooltip"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function ResizableBox(_ref) {
  var className = _ref.className,
      children = _ref.children,
      _ref$showHandle = _ref.showHandle,
      showHandle = _ref$showHandle === void 0 ? true : _ref$showHandle,
      _ref$__experimentalSh = _ref.__experimentalShowTooltip,
      showTooltip = _ref$__experimentalSh === void 0 ? false : _ref$__experimentalSh,
      _ref$__experimentalTo = _ref.__experimentalTooltipProps,
      tooltipProps = _ref$__experimentalTo === void 0 ? {} : _ref$__experimentalTo,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "children", "showHandle", "__experimentalShowTooltip", "__experimentalTooltipProps"]);
  // Removes the inline styles in the drag handles.
  var handleStylesOverrides = {
    width: null,
    height: null,
    top: null,
    right: null,
    bottom: null,
    left: null
  };
  var handleClassName = 'components-resizable-box__handle';
  var sideHandleClassName = 'components-resizable-box__side-handle';
  var cornerHandleClassName = 'components-resizable-box__corner-handle';
  return (0, _element.createElement)(_reResizable.Resizable, (0, _extends2.default)({
    className: (0, _classnames.default)('components-resizable-box__container', showHandle && 'has-show-handle', className),
    handleClasses: {
      top: (0, _classnames.default)(handleClassName, sideHandleClassName, 'components-resizable-box__handle-top'),
      right: (0, _classnames.default)(handleClassName, sideHandleClassName, 'components-resizable-box__handle-right'),
      bottom: (0, _classnames.default)(handleClassName, sideHandleClassName, 'components-resizable-box__handle-bottom'),
      left: (0, _classnames.default)(handleClassName, sideHandleClassName, 'components-resizable-box__handle-left'),
      topLeft: (0, _classnames.default)(handleClassName, cornerHandleClassName, 'components-resizable-box__handle-top', 'components-resizable-box__handle-left'),
      topRight: (0, _classnames.default)(handleClassName, cornerHandleClassName, 'components-resizable-box__handle-top', 'components-resizable-box__handle-right'),
      bottomRight: (0, _classnames.default)(handleClassName, cornerHandleClassName, 'components-resizable-box__handle-bottom', 'components-resizable-box__handle-right'),
      bottomLeft: (0, _classnames.default)(handleClassName, cornerHandleClassName, 'components-resizable-box__handle-bottom', 'components-resizable-box__handle-left')
    },
    handleStyles: {
      top: handleStylesOverrides,
      right: handleStylesOverrides,
      bottom: handleStylesOverrides,
      left: handleStylesOverrides,
      topLeft: handleStylesOverrides,
      topRight: handleStylesOverrides,
      bottomRight: handleStylesOverrides,
      bottomLeft: handleStylesOverrides
    }
  }, props), children, showTooltip && (0, _element.createElement)(_resizeTooltip.default, tooltipProps));
}

var _default = ResizableBox;
exports.default = _default;
//# sourceMappingURL=index.js.map