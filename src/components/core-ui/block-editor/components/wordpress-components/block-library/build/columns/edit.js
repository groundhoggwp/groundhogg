"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _utils = require("./utils");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Allowed blocks constant is passed to InnerBlocks precisely as specified here.
 * The contents of the array should never change.
 * The array should contain the name of each block that is allowed.
 * In columns block, the only block we allow is 'core/column'.
 *
 * @constant
 * @type {string[]}
 */
var ALLOWED_BLOCKS = ['core/column'];

function ColumnsEditContainer(_ref) {
  var attributes = _ref.attributes,
      updateAlignment = _ref.updateAlignment,
      updateColumns = _ref.updateColumns,
      clientId = _ref.clientId;
  var verticalAlignment = attributes.verticalAlignment;

  var _useSelect = (0, _data.useSelect)(function (select) {
    return {
      count: select('core/block-editor').getBlockCount(clientId)
    };
  }, [clientId]),
      count = _useSelect.count;

  var classes = (0, _classnames2.default)((0, _defineProperty2.default)({}, "are-vertically-aligned-".concat(verticalAlignment), verticalAlignment));
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: classes
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.BlockVerticalAlignmentToolbar, {
    onChange: updateAlignment,
    value: verticalAlignment
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, null, (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Columns'),
    value: count,
    onChange: function onChange(value) {
      return updateColumns(count, value);
    },
    min: 2,
    max: Math.max(6, count)
  }), count > 6 && (0, _element.createElement)(_components.Notice, {
    status: "warning",
    isDismissible: false
  }, (0, _i18n.__)('This column count exceeds the recommended amount and may cause visual breakage.')))), (0, _element.createElement)(_blockEditor.InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS,
    orientation: "horizontal",
    __experimentalTagName: "div",
    __experimentalPassedProps: blockWrapperProps,
    renderAppender: false
  }));
}

var ColumnsEditContainerWrapper = (0, _data.withDispatch)(function (dispatch, ownProps, registry) {
  return {
    /**
     * Update all child Column blocks with a new vertical alignment setting
     * based on whatever alignment is passed in. This allows change to parent
     * to overide anything set on a individual column basis.
     *
     * @param {string} verticalAlignment the vertical alignment setting
     */
    updateAlignment: function updateAlignment(verticalAlignment) {
      var clientId = ownProps.clientId,
          setAttributes = ownProps.setAttributes;

      var _dispatch = dispatch('core/block-editor'),
          updateBlockAttributes = _dispatch.updateBlockAttributes;

      var _registry$select = registry.select('core/block-editor'),
          getBlockOrder = _registry$select.getBlockOrder; // Update own alignment.


      setAttributes({
        verticalAlignment: verticalAlignment
      }); // Update all child Column Blocks to match

      var innerBlockClientIds = getBlockOrder(clientId);
      innerBlockClientIds.forEach(function (innerBlockClientId) {
        updateBlockAttributes(innerBlockClientId, {
          verticalAlignment: verticalAlignment
        });
      });
    },

    /**
     * Updates the column count, including necessary revisions to child Column
     * blocks to grant required or redistribute available space.
     *
     * @param {number} previousColumns Previous column count.
     * @param {number} newColumns      New column count.
     */
    updateColumns: function updateColumns(previousColumns, newColumns) {
      var clientId = ownProps.clientId;

      var _dispatch2 = dispatch('core/block-editor'),
          replaceInnerBlocks = _dispatch2.replaceInnerBlocks;

      var _registry$select2 = registry.select('core/block-editor'),
          getBlocks = _registry$select2.getBlocks;

      var innerBlocks = getBlocks(clientId);
      var hasExplicitWidths = (0, _utils.hasExplicitColumnWidths)(innerBlocks); // Redistribute available width for existing inner blocks.

      var isAddingColumn = newColumns > previousColumns;

      if (isAddingColumn && hasExplicitWidths) {
        // If adding a new column, assign width to the new column equal to
        // as if it were `1 / columns` of the total available space.
        var newColumnWidth = (0, _utils.toWidthPrecision)(100 / newColumns); // Redistribute in consideration of pending block insertion as
        // constraining the available working width.

        var widths = (0, _utils.getRedistributedColumnWidths)(innerBlocks, 100 - newColumnWidth);
        innerBlocks = [].concat((0, _toConsumableArray2.default)((0, _utils.getMappedColumnWidths)(innerBlocks, widths)), (0, _toConsumableArray2.default)((0, _lodash.times)(newColumns - previousColumns, function () {
          return (0, _blocks.createBlock)('core/column', {
            width: newColumnWidth
          });
        })));
      } else if (isAddingColumn) {
        innerBlocks = [].concat((0, _toConsumableArray2.default)(innerBlocks), (0, _toConsumableArray2.default)((0, _lodash.times)(newColumns - previousColumns, function () {
          return (0, _blocks.createBlock)('core/column');
        })));
      } else {
        // The removed column will be the last of the inner blocks.
        innerBlocks = (0, _lodash.dropRight)(innerBlocks, previousColumns - newColumns);

        if (hasExplicitWidths) {
          // Redistribute as if block is already removed.
          var _widths = (0, _utils.getRedistributedColumnWidths)(innerBlocks, 100);

          innerBlocks = (0, _utils.getMappedColumnWidths)(innerBlocks, _widths);
        }
      }

      replaceInnerBlocks(clientId, innerBlocks, false);
    }
  };
})(ColumnsEditContainer);

var createBlocksFromInnerBlocksTemplate = function createBlocksFromInnerBlocksTemplate(innerBlocksTemplate) {
  return (0, _lodash.map)(innerBlocksTemplate, function (_ref2) {
    var _ref3 = (0, _slicedToArray2.default)(_ref2, 3),
        name = _ref3[0],
        attributes = _ref3[1],
        _ref3$ = _ref3[2],
        innerBlocks = _ref3$ === void 0 ? [] : _ref3$;

    return (0, _blocks.createBlock)(name, attributes, createBlocksFromInnerBlocksTemplate(innerBlocks));
  });
};

function Placeholder(_ref4) {
  var clientId = _ref4.clientId,
      name = _ref4.name,
      setAttributes = _ref4.setAttributes;

  var _useSelect2 = (0, _data.useSelect)(function (select) {
    var _select = select('core/blocks'),
        getBlockVariations = _select.getBlockVariations,
        getBlockType = _select.getBlockType,
        getDefaultBlockVariation = _select.getDefaultBlockVariation;

    return {
      blockType: getBlockType(name),
      defaultVariation: getDefaultBlockVariation(name, 'block'),
      variations: getBlockVariations(name, 'block')
    };
  }, [name]),
      blockType = _useSelect2.blockType,
      defaultVariation = _useSelect2.defaultVariation,
      variations = _useSelect2.variations;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      replaceInnerBlocks = _useDispatch.replaceInnerBlocks;

  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();
  return (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)(_blockEditor.__experimentalBlockVariationPicker, {
    icon: (0, _lodash.get)(blockType, ['icon', 'src']),
    label: (0, _lodash.get)(blockType, ['title']),
    variations: variations,
    onSelect: function onSelect() {
      var nextVariation = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : defaultVariation;

      if (nextVariation.attributes) {
        setAttributes(nextVariation.attributes);
      }

      if (nextVariation.innerBlocks) {
        replaceInnerBlocks(clientId, createBlocksFromInnerBlocksTemplate(nextVariation.innerBlocks));
      }
    },
    allowSkip: true
  }));
}

var ColumnsEdit = function ColumnsEdit(props) {
  var clientId = props.clientId;
  var hasInnerBlocks = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getBlocks(clientId).length > 0;
  }, [clientId]);
  var Component = hasInnerBlocks ? ColumnsEditContainerWrapper : Placeholder;
  return (0, _element.createElement)(Component, props);
};

var _default = ColumnsEdit;
exports.default = _default;
//# sourceMappingURL=edit.js.map