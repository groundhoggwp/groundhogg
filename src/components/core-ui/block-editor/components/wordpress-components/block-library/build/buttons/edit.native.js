"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _blocks = require("@wordpress/blocks");

var _lodash = require("lodash");

var _button = require("../button/");

var _editor = _interopRequireDefault(require("./editor.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var ALLOWED_BLOCKS = [_button.name];
var BUTTONS_TEMPLATE = [['core/button']];

function ButtonsEdit(_ref) {
  var isSelected = _ref.isSelected,
      attributes = _ref.attributes,
      onDelete = _ref.onDelete,
      onAddNextButton = _ref.onAddNextButton,
      shouldDelete = _ref.shouldDelete,
      isInnerButtonSelected = _ref.isInnerButtonSelected;
  var align = attributes.align;

  var _useResizeObserver = (0, _compose.useResizeObserver)(),
      _useResizeObserver2 = (0, _slicedToArray2.default)(_useResizeObserver, 2),
      resizeObserver = _useResizeObserver2[0],
      sizes = _useResizeObserver2[1];

  var _useState = (0, _element.useState)(0),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      maxWidth = _useState2[0],
      setMaxWidth = _useState2[1];

  var shouldRenderFooterAppender = isSelected || isInnerButtonSelected;
  var spacing = _editor.default.spacing.marginLeft;
  (0, _element.useEffect)(function () {
    var margins = 2 * _editor.default.parent.marginRight;

    var _ref2 = sizes || {},
        width = _ref2.width;

    if (width) {
      setMaxWidth(width - margins);
    }
  }, [sizes]);
  var debounceAddNextButton = (0, _lodash.debounce)(onAddNextButton, 200);
  var renderFooterAppender = (0, _element.useRef)(function () {
    return (0, _element.createElement)(_reactNative.View, {
      style: _editor.default.appenderContainer
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.ButtonBlockAppender, {
      isFloating: true,
      onAddBlock: debounceAddNextButton
    }));
  }); // Inside buttons block alignment options are not supported.

  var alignmentHooksSetting = {
    isEmbedButton: true
  };
  return (0, _element.createElement)(_blockEditor.__experimentalAlignmentHookSettingsProvider, {
    value: alignmentHooksSetting
  }, resizeObserver, (0, _element.createElement)(_blockEditor.InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS,
    template: BUTTONS_TEMPLATE,
    renderFooterAppender: shouldRenderFooterAppender && renderFooterAppender.current,
    orientation: "horizontal",
    horizontalAlignment: align,
    onDeleteBlock: shouldDelete ? onDelete : undefined,
    onAddBlock: debounceAddNextButton,
    parentWidth: maxWidth,
    marginHorizontal: spacing,
    marginVertical: spacing
  }));
}

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select, _ref3) {
  var clientId = _ref3.clientId;

  var _select = select('core/block-editor'),
      getBlockCount = _select.getBlockCount,
      getBlockParents = _select.getBlockParents,
      getSelectedBlockClientId = _select.getSelectedBlockClientId;

  var selectedBlockClientId = getSelectedBlockClientId();
  var selectedBlockParents = getBlockParents(selectedBlockClientId, true);
  return {
    // The purpose of `shouldDelete` check is giving the ability to pass to
    // mobile toolbar function called `onDelete` which removes the whole
    // `Buttons` container along with the last inner button when
    // there is exactly one button.
    shouldDelete: getBlockCount(clientId) === 1,
    isInnerButtonSelected: selectedBlockParents[0] === clientId
  };
}), (0, _data.withDispatch)(function (dispatch, _ref4, registry) {
  var clientId = _ref4.clientId;

  var _dispatch = dispatch('core/block-editor'),
      selectBlock = _dispatch.selectBlock,
      removeBlock = _dispatch.removeBlock,
      insertBlock = _dispatch.insertBlock;

  var _registry$select = registry.select('core/block-editor'),
      getBlockOrder = _registry$select.getBlockOrder;

  return {
    // The purpose of `onAddNextButton` is giving the ability to automatically
    // adding `Button` inside `Buttons` block on the appender press event.
    onAddNextButton: function onAddNextButton(selectedId) {
      var order = getBlockOrder(clientId);
      var selectedButtonIndex = order.findIndex(function (i) {
        return i === selectedId;
      });
      var index = selectedButtonIndex === -1 ? order.length + 1 : selectedButtonIndex;
      var insertedBlock = (0, _blocks.createBlock)('core/button');
      insertBlock(insertedBlock, index, clientId);
      selectBlock(insertedBlock.clientId);
    },
    onDelete: function onDelete() {
      removeBlock(clientId);
    }
  };
}))(ButtonsEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map