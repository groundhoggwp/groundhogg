import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { __experimentalGetSettings, dateI18n } from '@wordpress/date';
import { InspectorControls, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { PanelBody, CustomSelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
export default function Edit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context,
      setAttributes = _ref.setAttributes;
  var className = attributes.className,
      format = attributes.format;
  var commentId = context.commentId;

  var settings = __experimentalGetSettings();

  var _useEntityProp = useEntityProp('root', 'site', 'date_format'),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 1),
      siteDateFormat = _useEntityProp2[0];

  var _useEntityProp3 = useEntityProp('root', 'comment', 'date', commentId),
      _useEntityProp4 = _slicedToArray(_useEntityProp3, 1),
      date = _useEntityProp4[0];

  var formatOptions = Object.values(settings.formats).map(function (formatOption) {
    return {
      key: formatOption,
      name: dateI18n(formatOption, date)
    };
  });
  var resolvedFormat = format || siteDateFormat || settings.formats.date;
  var blockWrapperProps = useBlockWrapperProps({
    className: className
  });
  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
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
  }))), createElement("div", blockWrapperProps, createElement("time", {
    dateTime: dateI18n('c', date)
  }, dateI18n(resolvedFormat, date))));
}
//# sourceMappingURL=edit.js.map