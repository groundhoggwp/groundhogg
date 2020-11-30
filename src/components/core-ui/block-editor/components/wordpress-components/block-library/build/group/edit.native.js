"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _reactNative = require("react-native");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _editor = _interopRequireDefault(require("./editor.scss"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function GroupEdit(_ref) {
  var attributes = _ref.attributes,
      hasInnerBlocks = _ref.hasInnerBlocks,
      isSelected = _ref.isSelected,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var align = attributes.align;
  var isFullWidth = align === _components.WIDE_ALIGNMENTS.alignments.full;
  var renderAppender = (0, _element.useCallback)(function () {
    return (0, _element.createElement)(_reactNative.View, {
      style: [isFullWidth && hasInnerBlocks && _editor.default.fullWidthAppender]
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.ButtonBlockAppender, null));
  }, [align, hasInnerBlocks]);

  if (!isSelected && !hasInnerBlocks) {
    return (0, _element.createElement)(_reactNative.View, {
      style: [getStylesFromColorScheme(_editor.default.groupPlaceholder, _editor.default.groupPlaceholderDark), !hasInnerBlocks && _objectSpread(_objectSpread({}, _editor.default.marginVerticalDense), _editor.default.marginHorizontalNone)]
    });
  }

  return (0, _element.createElement)(_reactNative.View, {
    style: [isSelected && hasInnerBlocks && _editor.default.innerBlocks, isSelected && !hasInnerBlocks && isFullWidth && _editor.default.fullWidth]
  }, (0, _element.createElement)(_blockEditor.InnerBlocks, {
    renderAppender: isSelected && renderAppender
  }));
}

var _default = (0, _compose.compose)([(0, _blockEditor.withColors)('backgroundColor'), (0, _data.withSelect)(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlock = _select.getBlock;

  var block = getBlock(clientId);
  return {
    hasInnerBlocks: !!(block && block.innerBlocks.length)
  };
}), _compose.withPreferredColorScheme])(GroupEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map