"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostDateEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _coreData = require("@wordpress/core-data");

var _date = require("@wordpress/date");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function PostDateEdit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context,
      setAttributes = _ref.setAttributes;
  var textAlign = attributes.textAlign,
      format = attributes.format;
  var postId = context.postId,
      postType = context.postType;

  var _useEntityProp = (0, _coreData.useEntityProp)('root', 'site', 'date_format'),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 1),
      siteFormat = _useEntityProp2[0];

  var _useEntityProp3 = (0, _coreData.useEntityProp)('postType', postType, 'date', postId),
      _useEntityProp4 = (0, _slicedToArray2.default)(_useEntityProp3, 2),
      date = _useEntityProp4[0],
      setDate = _useEntityProp4[1];

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isPickerOpen = _useState2[0],
      setIsPickerOpen = _useState2[1];

  var settings = (0, _date.__experimentalGetSettings)(); // To know if the current time format is a 12 hour time, look for "a".
  // Also make sure this "a" is not escaped by a "/".

  var is12Hour = /a(?!\\)/i.test(settings.formats.time.toLowerCase() // Test only for the lower case "a".
  .replace(/\\\\/g, '') // Replace "//" with empty strings.
  .split('').reverse().join('') // Reverse the string and test for "a" not followed by a slash.
  );
  var formatOptions = Object.values(settings.formats).map(function (formatOption) {
    return {
      key: formatOption,
      name: (0, _date.dateI18n)(formatOption, date)
    };
  });
  var resolvedFormat = format || siteFormat || settings.formats.date;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  }), date && (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
    icon: _icons.edit,
    title: (0, _i18n.__)('Change Date'),
    onClick: function onClick() {
      return setIsPickerOpen(function (_isPickerOpen) {
        return !_isPickerOpen;
      });
    }
  }))), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Format settings')
  }, (0, _element.createElement)(_components.CustomSelectControl, {
    hideLabelFromVision: true,
    label: (0, _i18n.__)('Date Format'),
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
  }))), (0, _element.createElement)("div", blockWrapperProps, date && (0, _element.createElement)("time", {
    dateTime: (0, _date.dateI18n)('c', date)
  }, (0, _date.dateI18n)(resolvedFormat, date), isPickerOpen && (0, _element.createElement)(_components.Popover, {
    onClose: setIsPickerOpen.bind(null, false)
  }, (0, _element.createElement)(_components.DateTimePicker, {
    currentDate: date,
    onChange: setDate,
    is12Hour: is12Hour
  }))), !date && (0, _i18n.__)('No Date')));
}
//# sourceMappingURL=edit.js.map