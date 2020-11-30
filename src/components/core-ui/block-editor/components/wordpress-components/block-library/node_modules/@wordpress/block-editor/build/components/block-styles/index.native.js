"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _utils = require("./utils");

var _preview = _interopRequireDefault(require("./preview"));

var _style = _interopRequireDefault(require("./style.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockStyles(_ref) {
  var clientId = _ref.clientId,
      url = _ref.url;

  var selector = function selector(select) {
    var _select = select('core/block-editor'),
        getBlock = _select.getBlock;

    var _select2 = select('core/blocks'),
        getBlockStyles = _select2.getBlockStyles;

    var block = getBlock(clientId);
    return {
      styles: getBlockStyles(block.name),
      className: block.attributes.className || ''
    };
  };

  var _useSelect = (0, _data.useSelect)(selector, [clientId]),
      styles = _useSelect.styles,
      className = _useSelect.className;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  if (!styles || styles.length === 0) {
    return null;
  }

  var renderedStyles = (0, _lodash.find)(styles, 'isDefault') ? styles : [{
    name: 'default',
    label: (0, _i18n._x)('Default', 'block style'),
    isDefault: true
  }].concat((0, _toConsumableArray2.default)(styles));
  var activeStyle = (0, _utils.getActiveStyle)(renderedStyles, className);
  return (0, _element.createElement)(_reactNative.ScrollView, {
    horizontal: true,
    showsHorizontalScrollIndicator: false,
    contentContainerStyle: _style.default.content
  }, renderedStyles.map(function (style) {
    var styleClassName = (0, _utils.replaceActiveStyle)(className, activeStyle, style);
    var isActive = activeStyle === style;

    var onStylePress = function onStylePress() {
      updateBlockAttributes(clientId, {
        className: styleClassName
      });
    };

    return (0, _element.createElement)(_preview.default, {
      onPress: onStylePress,
      isActive: isActive,
      key: style.name,
      style: style,
      url: url
    });
  }));
}

var _default = BlockStyles;
exports.default = _default;
//# sourceMappingURL=index.native.js.map