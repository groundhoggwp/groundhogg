"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _rovingTabIndexContext = require("./roving-tab-index-context");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var _default = (0, _element.forwardRef)(function RovingTabIndexItem(_ref, forwardedRef) {
  var children = _ref.children,
      Component = _ref.as,
      props = (0, _objectWithoutProperties2.default)(_ref, ["children", "as"]);
  var localRef = (0, _element.useRef)();
  var ref = forwardedRef || localRef;

  var _useRovingTabIndexCon = (0, _rovingTabIndexContext.useRovingTabIndexContext)(),
      lastFocusedElement = _useRovingTabIndexCon.lastFocusedElement,
      setLastFocusedElement = _useRovingTabIndexCon.setLastFocusedElement;

  var tabIndex;

  if (lastFocusedElement) {
    tabIndex = lastFocusedElement === ref.current ? 0 : -1;
  }

  var onFocus = function onFocus(event) {
    return setLastFocusedElement(event.target);
  };

  var allProps = _objectSpread({
    ref: ref,
    tabIndex: tabIndex,
    onFocus: onFocus
  }, props);

  if (typeof children === 'function') {
    return children(allProps);
  }

  return (0, _element.createElement)(Component, allProps, children);
});

exports.default = _default;
//# sourceMappingURL=roving-tab-index-item.js.map