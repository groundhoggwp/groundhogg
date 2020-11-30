import _extends from "@babel/runtime/helpers/esm/extends";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
import { dropRight, times, map, compact, delay } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl, FooterMessageControl } from '@wordpress/components';
import { InspectorControls, InnerBlocks, BlockControls, BlockVerticalAlignmentToolbar, BlockVariationPicker } from '@wordpress/block-editor';
import { withDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState, useMemo } from '@wordpress/element';
import { useResizeObserver } from '@wordpress/compose';
import { createBlock } from '@wordpress/blocks';
import { columns } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import variations from './variations';
import styles from './editor.scss';
import { getColumnWidths } from './utils';
import ColumnsPreview from '../column/column-preview';
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
/**
 * Number of columns to assume for template in case the user opts to skip
 * template option selection.
 *
 * @type {number}
 */

var DEFAULT_COLUMNS_NUM = 2;
/**
 * Minimum number of columns in a row
 *
 * @type {number}
 */

var MIN_COLUMNS_NUM = 1;
/**
 * Maximum number of columns in a row
 *
 * @type {number}
 */

var MAX_COLUMNS_NUM_IN_ROW = 3;
var BREAKPOINTS = {
  mobile: 480,
  large: 768
};

function ColumnsEditContainer(_ref) {
  var attributes = _ref.attributes,
      updateAlignment = _ref.updateAlignment,
      updateColumns = _ref.updateColumns,
      columnCount = _ref.columnCount,
      isSelected = _ref.isSelected,
      onAddNextColumn = _ref.onAddNextColumn,
      onDeleteBlock = _ref.onDeleteBlock,
      innerColumns = _ref.innerColumns,
      updateInnerColumnWidth = _ref.updateInnerColumnWidth;

  var _useResizeObserver = useResizeObserver(),
      _useResizeObserver2 = _slicedToArray(_useResizeObserver, 2),
      resizeListener = _useResizeObserver2[0],
      sizes = _useResizeObserver2[1];

  var _useState = useState(MIN_COLUMNS_NUM),
      _useState2 = _slicedToArray(_useState, 2),
      columnsInRow = _useState2[0],
      setColumnsInRow = _useState2[1];

  var verticalAlignment = attributes.verticalAlignment;

  var _ref2 = sizes || {},
      width = _ref2.width;

  var newColumnCount = columnCount || DEFAULT_COLUMNS_NUM;
  useEffect(function () {
    updateColumns(columnCount, newColumnCount);
  }, []);
  useEffect(function () {
    if (width) {
      setColumnsInRow(getColumnsInRow(width, newColumnCount));
    }
  }, [columnCount]);
  useEffect(function () {
    if (width) {
      setColumnsInRow(getColumnsInRow(width, columnCount));
    }
  }, [width]);
  var contentStyle = useMemo(function () {
    var minWidth = Math.min(width, styles.columnsContainer.maxWidth);
    var columnBaseWidth = minWidth / columnsInRow;
    var columnWidth = columnBaseWidth;

    if (columnsInRow > 1) {
      var margins = columnsInRow * Math.min(columnsInRow, MAX_COLUMNS_NUM_IN_ROW) * styles.columnMargin.marginLeft;
      columnWidth = (minWidth - margins) / columnsInRow;
    }

    return {
      width: columnWidth
    };
  }, [width, columnsInRow]);

  var getColumnsInRow = function getColumnsInRow(containerWidth, columnsNumber) {
    if (containerWidth < BREAKPOINTS.mobile) {
      // show only 1 Column in row for mobile breakpoint container width
      return 1;
    } else if (containerWidth < BREAKPOINTS.large) {
      // show the maximum number of columns in a row for large breakpoint container width
      return Math.min(Math.max(1, columnCount), MAX_COLUMNS_NUM_IN_ROW);
    } // show all Column in one row


    return Math.max(1, columnsNumber);
  };

  var renderAppender = function renderAppender() {
    if (isSelected) {
      return createElement(View, {
        style: columnCount === 0 && {
          width: width
        }
      }, createElement(InnerBlocks.ButtonBlockAppender, {
        onAddBlock: onAddNextColumn
      }));
    }

    return null;
  };

  var getColumnsSliders = function getColumnsSliders() {
    var columnWidths = Object.values(getColumnWidths(innerColumns, columnCount));
    return innerColumns.map(function (column, index) {
      return createElement(RangeControl, {
        min: 1,
        max: 100,
        step: 0.1,
        value: columnWidths[index],
        onChange: function onChange(value) {
          return updateInnerColumnWidth(value, column.clientId);
        },
        cellContainerStyle: styles.cellContainerStyle,
        decimalNum: 1,
        rangePreview: createElement(ColumnsPreview, {
          columnWidths: columnWidths,
          selectedColumnIndex: index
        }),
        key: column.clientId,
        shouldDisplayTextInput: false
      });
    });
  };

  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Columns Settings')
  }, createElement(RangeControl, {
    label: __('Number of columns'),
    icon: columns,
    value: columnCount,
    onChange: function onChange(value) {
      return updateColumns(columnCount, value);
    },
    min: MIN_COLUMNS_NUM,
    max: columnCount + 1,
    type: "stepper"
  }), getColumnsSliders()), createElement(PanelBody, null, createElement(FooterMessageControl, {
    label: __('Note: Column layout may vary between themes and screen sizes')
  }))), createElement(BlockControls, null, createElement(BlockVerticalAlignmentToolbar, {
    onChange: updateAlignment,
    value: verticalAlignment
  })), createElement(View, {
    style: isSelected && styles.innerBlocksSelected
  }, resizeListener, width && createElement(InnerBlocks, {
    renderAppender: renderAppender,
    orientation: columnsInRow > 1 ? 'horizontal' : undefined,
    horizontal: true,
    allowedBlocks: ALLOWED_BLOCKS,
    contentResizeMode: "stretch",
    onAddBlock: onAddNextColumn,
    onDeleteBlock: columnCount === 1 ? onDeleteBlock : undefined,
    contentStyle: contentStyle,
    parentWidth: width
  })));
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
    updateInnerColumnWidth: function updateInnerColumnWidth(value, columnId) {
      var _dispatch2 = dispatch('core/block-editor'),
          updateBlockAttributes = _dispatch2.updateBlockAttributes;

      updateBlockAttributes(columnId, {
        width: value
      });
    },
    updateBlockSettings: function updateBlockSettings(settings) {
      var clientId = ownProps.clientId;

      var _dispatch3 = dispatch('core/block-editor'),
          updateBlockListSettings = _dispatch3.updateBlockListSettings;

      updateBlockListSettings(clientId, settings);
    },

    /**
     * Updates the column columnCount, including necessary revisions to child Column
     * blocks to grant required or redistribute available space.
     *
     * @param {number} previousColumns Previous column columnCount.
     * @param {number} newColumns      New column columnCount.
     */
    updateColumns: function updateColumns(previousColumns, newColumns) {
      var clientId = ownProps.clientId;

      var _dispatch4 = dispatch('core/block-editor'),
          replaceInnerBlocks = _dispatch4.replaceInnerBlocks;

      var _registry$select2 = registry.select('core/block-editor'),
          getBlocks = _registry$select2.getBlocks,
          getBlockAttributes = _registry$select2.getBlockAttributes;

      var innerBlocks = getBlocks(clientId); // Redistribute available width for existing inner blocks.

      var isAddingColumn = newColumns > previousColumns;

      if (isAddingColumn) {
        // Get verticalAlignment from Columns block to set the same to new Column
        var _ref3 = getBlockAttributes(clientId) || {},
            verticalAlignment = _ref3.verticalAlignment;

        innerBlocks = [].concat(_toConsumableArray(innerBlocks), _toConsumableArray(times(newColumns - previousColumns, function () {
          return createBlock('core/column', {
            verticalAlignment: verticalAlignment
          });
        })));
      } else {
        // The removed column will be the last of the inner blocks.
        innerBlocks = dropRight(innerBlocks, previousColumns - newColumns);
      }

      replaceInnerBlocks(clientId, innerBlocks, false);
    },
    onAddNextColumn: function onAddNextColumn() {
      var clientId = ownProps.clientId;

      var _dispatch5 = dispatch('core/block-editor'),
          replaceInnerBlocks = _dispatch5.replaceInnerBlocks,
          selectBlock = _dispatch5.selectBlock;

      var _registry$select3 = registry.select('core/block-editor'),
          getBlocks = _registry$select3.getBlocks,
          getBlockAttributes = _registry$select3.getBlockAttributes; // Get verticalAlignment from Columns block to set the same to new Column


      var _getBlockAttributes = getBlockAttributes(clientId),
          verticalAlignment = _getBlockAttributes.verticalAlignment;

      var innerBlocks = getBlocks(clientId);
      var insertedBlock = createBlock('core/column', {
        verticalAlignment: verticalAlignment
      });
      replaceInnerBlocks(clientId, [].concat(_toConsumableArray(innerBlocks), [insertedBlock]), true);
      selectBlock(insertedBlock.clientId);
    },
    onDeleteBlock: function onDeleteBlock() {
      var clientId = ownProps.clientId;

      var _dispatch6 = dispatch('core/block-editor'),
          removeBlock = _dispatch6.removeBlock;

      removeBlock(clientId);
    }
  };
})(ColumnsEditContainer);

var ColumnsEdit = function ColumnsEdit(props) {
  var clientId = props.clientId,
      isSelected = props.isSelected;

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlockCount = _select.getBlockCount,
        getBlock = _select.getBlock;

    var block = getBlock(clientId);
    var innerBlocks = block === null || block === void 0 ? void 0 : block.innerBlocks;
    var isContentEmpty = map(innerBlocks, function (innerBlock) {
      return innerBlock.innerBlocks.length;
    });
    return {
      columnCount: getBlockCount(clientId),
      isDefaultColumns: !compact(isContentEmpty).length,
      innerColumns: innerBlocks
    };
  }, [clientId]),
      columnCount = _useSelect.columnCount,
      isDefaultColumns = _useSelect.isDefaultColumns,
      _useSelect$innerColum = _useSelect.innerColumns,
      innerColumns = _useSelect$innerColum === void 0 ? [] : _useSelect$innerColum;

  var _useState3 = useState(false),
      _useState4 = _slicedToArray(_useState3, 2),
      isVisible = _useState4[0],
      setIsVisible = _useState4[1];

  useEffect(function () {
    if (isSelected && isDefaultColumns) {
      delay(function () {
        return setIsVisible(true);
      }, 100);
    }
  }, []);
  return createElement(Fragment, null, createElement(ColumnsEditContainerWrapper, _extends({
    columnCount: columnCount,
    innerColumns: innerColumns
  }, props)), createElement(BlockVariationPicker, {
    variations: variations,
    onClose: function onClose() {
      return setIsVisible(false);
    },
    clientId: clientId,
    isVisible: isVisible
  }));
};

export default ColumnsEdit;
//# sourceMappingURL=edit.native.js.map