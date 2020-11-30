import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { get, times } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl } from '@wordpress/components';
import { BlockControls, BlockAlignmentToolbar, InspectorControls, RichText } from '@wordpress/block-editor';
import deprecated from '@wordpress/deprecated';
export default function TextColumnsEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      className = _ref.className;
  var width = attributes.width,
      content = attributes.content,
      columns = attributes.columns;
  deprecated('The Text Columns block', {
    alternative: 'the Columns block',
    plugin: 'Gutenberg'
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(BlockAlignmentToolbar, {
    value: width,
    onChange: function onChange(nextWidth) {
      return setAttributes({
        width: nextWidth
      });
    },
    controls: ['center', 'wide', 'full']
  })), createElement(InspectorControls, null, createElement(PanelBody, null, createElement(RangeControl, {
    label: __('Columns'),
    value: columns,
    onChange: function onChange(value) {
      return setAttributes({
        columns: value
      });
    },
    min: 2,
    max: 4,
    required: true
  }))), createElement("div", {
    className: "".concat(className, " align").concat(width, " columns-").concat(columns)
  }, times(columns, function (index) {
    return createElement("div", {
      className: "wp-block-column",
      key: "column-".concat(index)
    }, createElement(RichText, {
      tagName: "p",
      value: get(content, [index, 'children']),
      onChange: function onChange(nextContent) {
        setAttributes({
          content: [].concat(_toConsumableArray(content.slice(0, index)), [{
            children: nextContent
          }], _toConsumableArray(content.slice(index + 1)))
        });
      },
      placeholder: __('New Column')
    }));
  })));
}
//# sourceMappingURL=edit.js.map