"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _inserter = _interopRequireDefault(require("../inserter"));

var _styles = _interopRequireDefault(require("./styles.scss"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function ButtonBlockAppender(_ref) {
  var rootClientId = _ref.rootClientId,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      showSeparator = _ref.showSeparator,
      _ref$isFloating = _ref.isFloating,
      isFloating = _ref$isFloating === void 0 ? false : _ref$isFloating,
      onAddBlock = _ref.onAddBlock;

  var appenderStyle = _objectSpread(_objectSpread({}, _styles.default.appender), getStylesFromColorScheme(_styles.default.appenderLight, _styles.default.appenderDark));

  var addBlockButtonStyle = getStylesFromColorScheme(_styles.default.addBlockButton, isFloating ? _styles.default.floatingAddBlockButtonDark : _styles.default.addBlockButtonDark);
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_inserter.default, {
    rootClientId: rootClientId,
    renderToggle: function renderToggle(_ref2) {
      var onToggle = _ref2.onToggle,
          disabled = _ref2.disabled,
          isOpen = _ref2.isOpen;
      return (0, _element.createElement)(_components.Button, {
        onClick: onAddBlock || onToggle,
        "aria-expanded": isOpen,
        disabled: disabled,
        fixedRatio: false
      }, (0, _element.createElement)(_reactNative.View, {
        style: [appenderStyle, isFloating && _styles.default.floatingAppender]
      }, (0, _element.createElement)(_icons.Icon, {
        icon: _icons.plusCircleFilled,
        style: addBlockButtonStyle,
        color: addBlockButtonStyle.color,
        size: addBlockButtonStyle.size
      })));
    },
    isAppender: true,
    showSeparator: showSeparator
  }));
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/button-block-appender/README.md
 */


var _default = (0, _compose.withPreferredColorScheme)(ButtonBlockAppender);

exports.default = _default;
//# sourceMappingURL=index.native.js.map