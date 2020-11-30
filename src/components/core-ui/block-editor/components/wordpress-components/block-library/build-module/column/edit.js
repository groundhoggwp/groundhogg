import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { InnerBlocks, BlockControls, BlockVerticalAlignmentToolbar, InspectorControls, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

function ColumnEdit(_ref) {
  var _ref$attributes = _ref.attributes,
      verticalAlignment = _ref$attributes.verticalAlignment,
      width = _ref$attributes.width,
      setAttributes = _ref.setAttributes,
      clientId = _ref.clientId;
  var classes = classnames('block-core-columns', _defineProperty({}, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment));

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlockOrder = _select.getBlockOrder,
        getBlockRootClientId = _select.getBlockRootClientId;

    return {
      hasChildBlocks: getBlockOrder(clientId).length > 0,
      rootClientId: getBlockRootClientId(clientId)
    };
  }, [clientId]),
      hasChildBlocks = _useSelect.hasChildBlocks,
      rootClientId = _useSelect.rootClientId;

  var _useDispatch = useDispatch('core/block-editor'),
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  var updateAlignment = function updateAlignment(value) {
    // Update own alignment.
    setAttributes({
      verticalAlignment: value
    }); // Reset parent Columns block.

    updateBlockAttributes(rootClientId, {
      verticalAlignment: null
    });
  };

  var hasWidth = Number.isFinite(width);
  var blockWrapperProps = useBlockWrapperProps({
    className: classes,
    style: hasWidth ? {
      flexBasis: width + '%'
    } : undefined
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(BlockVerticalAlignmentToolbar, {
    onChange: updateAlignment,
    value: verticalAlignment
  })), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Column settings')
  }, createElement(RangeControl, {
    label: __('Percentage width'),
    value: width || '',
    onChange: function onChange(nextWidth) {
      setAttributes({
        width: nextWidth
      });
    },
    min: 0,
    max: 100,
    step: 0.1,
    required: true,
    allowReset: true,
    placeholder: width === undefined ? __('Auto') : undefined
  }))), createElement(InnerBlocks, {
    templateLock: false,
    renderAppender: hasChildBlocks ? undefined : InnerBlocks.ButtonBlockAppender,
    __experimentalTagName: "div",
    __experimentalPassedProps: blockWrapperProps
  }));
}

export default ColumnEdit;
//# sourceMappingURL=edit.js.map