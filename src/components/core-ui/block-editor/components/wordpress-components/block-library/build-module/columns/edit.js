import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { dropRight, get, map, times } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl, Notice } from '@wordpress/components';
import { InspectorControls, InnerBlocks, BlockControls, BlockVerticalAlignmentToolbar, __experimentalBlockVariationPicker, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { withDispatch, useDispatch, useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import { hasExplicitColumnWidths, getMappedColumnWidths, getRedistributedColumnWidths, toWidthPrecision } from './utils';
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

  var _useSelect = useSelect(function (select) {
    return {
      count: select('core/block-editor').getBlockCount(clientId)
    };
  }, [clientId]),
      count = _useSelect.count;

  var classes = classnames(_defineProperty({}, "are-vertically-aligned-".concat(verticalAlignment), verticalAlignment));
  var blockWrapperProps = useBlockWrapperProps({
    className: classes
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(BlockVerticalAlignmentToolbar, {
    onChange: updateAlignment,
    value: verticalAlignment
  })), createElement(InspectorControls, null, createElement(PanelBody, null, createElement(RangeControl, {
    label: __('Columns'),
    value: count,
    onChange: function onChange(value) {
      return updateColumns(count, value);
    },
    min: 2,
    max: Math.max(6, count)
  }), count > 6 && createElement(Notice, {
    status: "warning",
    isDismissible: false
  }, __('This column count exceeds the recommended amount and may cause visual breakage.')))), createElement(InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS,
    orientation: "horizontal",
    __experimentalTagName: "div",
    __experimentalPassedProps: blockWrapperProps,
    renderAppender: false
  }));
}

var ColumnsEditContainerWrapper = withDispatch(function (dispatch, ownProps, registry) {
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
      var hasExplicitWidths = hasExplicitColumnWidths(innerBlocks); // Redistribute available width for existing inner blocks.

      var isAddingColumn = newColumns > previousColumns;

      if (isAddingColumn && hasExplicitWidths) {
        // If adding a new column, assign width to the new column equal to
        // as if it were `1 / columns` of the total available space.
        var newColumnWidth = toWidthPrecision(100 / newColumns); // Redistribute in consideration of pending block insertion as
        // constraining the available working width.

        var widths = getRedistributedColumnWidths(innerBlocks, 100 - newColumnWidth);
        innerBlocks = [].concat(_toConsumableArray(getMappedColumnWidths(innerBlocks, widths)), _toConsumableArray(times(newColumns - previousColumns, function () {
          return createBlock('core/column', {
            width: newColumnWidth
          });
        })));
      } else if (isAddingColumn) {
        innerBlocks = [].concat(_toConsumableArray(innerBlocks), _toConsumableArray(times(newColumns - previousColumns, function () {
          return createBlock('core/column');
        })));
      } else {
        // The removed column will be the last of the inner blocks.
        innerBlocks = dropRight(innerBlocks, previousColumns - newColumns);

        if (hasExplicitWidths) {
          // Redistribute as if block is already removed.
          var _widths = getRedistributedColumnWidths(innerBlocks, 100);

          innerBlocks = getMappedColumnWidths(innerBlocks, _widths);
        }
      }

      replaceInnerBlocks(clientId, innerBlocks, false);
    }
  };
})(ColumnsEditContainer);

var createBlocksFromInnerBlocksTemplate = function createBlocksFromInnerBlocksTemplate(innerBlocksTemplate) {
  return map(innerBlocksTemplate, function (_ref2) {
    var _ref3 = _slicedToArray(_ref2, 3),
        name = _ref3[0],
        attributes = _ref3[1],
        _ref3$ = _ref3[2],
        innerBlocks = _ref3$ === void 0 ? [] : _ref3$;

    return createBlock(name, attributes, createBlocksFromInnerBlocksTemplate(innerBlocks));
  });
};

function Placeholder(_ref4) {
  var clientId = _ref4.clientId,
      name = _ref4.name,
      setAttributes = _ref4.setAttributes;

  var _useSelect2 = useSelect(function (select) {
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

  var _useDispatch = useDispatch('core/block-editor'),
      replaceInnerBlocks = _useDispatch.replaceInnerBlocks;

  var blockWrapperProps = useBlockWrapperProps();
  return createElement("div", blockWrapperProps, createElement(__experimentalBlockVariationPicker, {
    icon: get(blockType, ['icon', 'src']),
    label: get(blockType, ['title']),
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
  var hasInnerBlocks = useSelect(function (select) {
    return select('core/block-editor').getBlocks(clientId).length > 0;
  }, [clientId]);
  var Component = hasInnerBlocks ? ColumnsEditContainerWrapper : Placeholder;
  return createElement(Component, props);
};

export default ColumnsEdit;
//# sourceMappingURL=edit.js.map