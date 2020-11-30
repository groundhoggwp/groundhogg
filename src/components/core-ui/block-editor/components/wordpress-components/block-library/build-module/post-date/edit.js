import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useEntityProp } from '@wordpress/core-data';
import { useState } from '@wordpress/element';
import { __experimentalGetSettings, dateI18n } from '@wordpress/date';
import { AlignmentToolbar, BlockControls, InspectorControls, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton, Popover, DateTimePicker, PanelBody, CustomSelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { edit } from '@wordpress/icons';
export default function PostDateEdit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context,
      setAttributes = _ref.setAttributes;
  var textAlign = attributes.textAlign,
      format = attributes.format;
  var postId = context.postId,
      postType = context.postType;

  var _useEntityProp = useEntityProp('root', 'site', 'date_format'),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 1),
      siteFormat = _useEntityProp2[0];

  var _useEntityProp3 = useEntityProp('postType', postType, 'date', postId),
      _useEntityProp4 = _slicedToArray(_useEntityProp3, 2),
      date = _useEntityProp4[0],
      setDate = _useEntityProp4[1];

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isPickerOpen = _useState2[0],
      setIsPickerOpen = _useState2[1];

  var settings = __experimentalGetSettings(); // To know if the current time format is a 12 hour time, look for "a".
  // Also make sure this "a" is not escaped by a "/".


  var is12Hour = /a(?!\\)/i.test(settings.formats.time.toLowerCase() // Test only for the lower case "a".
  .replace(/\\\\/g, '') // Replace "//" with empty strings.
  .split('').reverse().join('') // Reverse the string and test for "a" not followed by a slash.
  );
  var formatOptions = Object.values(settings.formats).map(function (formatOption) {
    return {
      key: formatOption,
      name: dateI18n(formatOption, date)
    };
  });
  var resolvedFormat = format || siteFormat || settings.formats.date;
  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  }), date && createElement(ToolbarGroup, null, createElement(ToolbarButton, {
    icon: edit,
    title: __('Change Date'),
    onClick: function onClick() {
      return setIsPickerOpen(function (_isPickerOpen) {
        return !_isPickerOpen;
      });
    }
  }))), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Format settings')
  }, createElement(CustomSelectControl, {
    hideLabelFromVision: true,
    label: __('Date Format'),
    options: formatOptions,
    onChange: function onChange(_ref2) {
      var selectedItem = _ref2.selectedItem;
      return setAttributes({
        format: selectedItem.key
      });
    },
    value: formatOptions.find(function (option) {
      return option.key === resolvedFormat;
    })
  }))), createElement("div", blockWrapperProps, date && createElement("time", {
    dateTime: dateI18n('c', date)
  }, dateI18n(resolvedFormat, date), isPickerOpen && createElement(Popover, {
    onClose: setIsPickerOpen.bind(null, false)
  }, createElement(DateTimePicker, {
    currentDate: date,
    onChange: setDate,
    is12Hour: is12Hour
  }))), !date && __('No Date')));
}
//# sourceMappingURL=edit.js.map