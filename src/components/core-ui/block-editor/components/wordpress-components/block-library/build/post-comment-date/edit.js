"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Edit;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _coreData = require("@wordpress/core-data");

var _date = require("@wordpress/date");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

/**
 * WordPress dependencies
 */
function Edit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context,
      setAttributes = _ref.setAttributes;
  var className = attributes.className,
      format = attributes.format;
  var commentId = context.commentId;
  var settings = (0, _date.__experimentalGetSettings)();

  var _useEntityProp = (0, _coreData.useEntityProp)('root', 'site', 'date_format'),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 1),
      siteDateFormat = _useEntityProp2[0];

  var _useEntityProp3 = (0, _coreData.useEntityProp)('root', 'comment', 'date', commentId),
      _useEntityProp4 = (0, _slicedToArray2.default)(_useEntityProp3, 1),
      date = _useEntityProp4[0];

  var formatOptions = Object.values(settings.formats).map(function (formatOption) {
    return {
      key: formatOption,
      name: (0, _date.dateI18n)(formatOption, date)
    };
  });
  var resolvedFormat = format || siteDateFormat || settings.formats.date;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: className
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
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
  }))), (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)("time", {
    dateTime: (0, _date.dateI18n)('c', date)
  }, (0, _date.dateI18n)(resolvedFormat, date))));
}
//# sourceMappingURL=edit.js.map