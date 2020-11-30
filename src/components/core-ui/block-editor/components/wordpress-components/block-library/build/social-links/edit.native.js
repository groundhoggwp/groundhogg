"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _editor = _interopRequireDefault(require("./editor.scss"));

var _variations = _interopRequireDefault(require("../social-link/variations"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var ALLOWED_BLOCKS = _variations.default.map(function (v) {
  return "core/social-link-".concat(v.name);
}); // Template contains the links that show when start.


var TEMPLATE = [['core/social-link-wordpress', {
  service: 'wordpress',
  url: 'https://wordpress.org'
}], ['core/social-link-facebook', {
  service: 'facebook'
}], ['core/social-link-twitter', {
  service: 'twitter'
}], ['core/social-link-instagram', {
  service: 'instagram'
}]];

function SocialLinksEdit(_ref) {
  var shouldDelete = _ref.shouldDelete,
      onDelete = _ref.onDelete,
      isSelected = _ref.isSelected,
      isInnerIconSelected = _ref.isInnerIconSelected,
      innerBlocks = _ref.innerBlocks,
      attributes = _ref.attributes,
      activeInnerBlocks = _ref.activeInnerBlocks,
      getBlock = _ref.getBlock;

  var _useState = (0, _element.useState)(true),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      initialCreation = _useState2[0],
      setInitialCreation = _useState2[1];

  var shouldRenderFooterAppender = isSelected || isInnerIconSelected;
  var align = attributes.align;
  var spacing = _editor.default.spacing.marginLeft;
  (0, _element.useEffect)(function () {
    if (!shouldRenderFooterAppender) {
      setInitialCreation(false);
    }
  }, [shouldRenderFooterAppender]);
  var renderFooterAppender = (0, _element.useRef)(function () {
    return (0, _element.createElement)(_reactNative.View, null, (0, _element.createElement)(_blockEditor.InnerBlocks.ButtonBlockAppender, {
      isFloating: true
    }));
  });
  var placeholderStyle = (0, _compose.usePreferredColorSchemeStyle)(_editor.default.placeholder, _editor.default.placeholderDark);

  function renderPlaceholder() {
    return (0, _toConsumableArray2.default)(new Array(innerBlocks.length || 1)).map(function (_, index) {
      return (0, _element.createElement)(_reactNative.View, {
        style: placeholderStyle,
        key: index
      });
    });
  }

  function filterInnerBlocks(blockIds) {
    return blockIds.filter(function (blockId) {
      return getBlock(blockId).attributes.url;
    });
  }

  if (!shouldRenderFooterAppender && activeInnerBlocks.length === 0) {
    return (0, _element.createElement)(_reactNative.View, {
      style: _editor.default.placeholderWrapper
    }, renderPlaceholder());
  }

  return (0, _element.createElement)(_blockEditor.InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS,
    templateLock: false,
    template: initialCreation && TEMPLATE,
    renderFooterAppender: shouldRenderFooterAppender && renderFooterAppender.current,
    orientation: 'horizontal',
    onDeleteBlock: shouldDelete ? onDelete : undefined,
    marginVertical: spacing,
    marginHorizontal: spacing,
    horizontalAlignment: align,
    filterInnerBlocks: !shouldRenderFooterAppender && filterInnerBlocks
  });
}

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockCount = _select.getBlockCount,
      getBlockParents = _select.getBlockParents,
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getBlocks = _select.getBlocks,
      getBlock = _select.getBlock;

  var selectedBlockClientId = getSelectedBlockClientId();
  var selectedBlockParents = getBlockParents(selectedBlockClientId, true);
  var innerBlocks = getBlocks(clientId);
  var activeInnerBlocks = innerBlocks.filter(function (block) {
    var _block$attributes;

    return (_block$attributes = block.attributes) === null || _block$attributes === void 0 ? void 0 : _block$attributes.url;
  });
  return {
    shouldDelete: getBlockCount(clientId) === 1,
    isInnerIconSelected: selectedBlockParents[0] === clientId,
    innerBlocks: innerBlocks,
    activeInnerBlocks: activeInnerBlocks,
    getBlock: getBlock
  };
}), (0, _data.withDispatch)(function (dispatch, _ref3) {
  var clientId = _ref3.clientId;

  var _dispatch = dispatch('core/block-editor'),
      removeBlock = _dispatch.removeBlock;

  return {
    onDelete: function onDelete() {
      removeBlock(clientId, false);
    }
  };
}))(SocialLinksEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map