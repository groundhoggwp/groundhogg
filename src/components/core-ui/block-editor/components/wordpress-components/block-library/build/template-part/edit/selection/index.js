"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = TemplatePartSelection;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _blockEditor = require("@wordpress/block-editor");

var _templatePartPreviews = _interopRequireDefault(require("./template-part-previews"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function TemplatePartSelection(_ref) {
  var setAttributes = _ref.setAttributes,
      onClose = _ref.onClose;

  var _useState = (0, _element.useState)(''),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      filterValue = _useState2[0],
      setFilterValue = _useState2[1];

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.__experimentalSearchForm, {
    value: filterValue,
    onChange: setFilterValue,
    className: "wp-block-template-part__selection-preview-search-form"
  }), (0, _element.createElement)("div", {
    className: "wp-block-template-part__selection-preview-container"
  }, (0, _element.createElement)(_templatePartPreviews.default, {
    setAttributes: setAttributes,
    filterValue: filterValue,
    onClose: onClose
  })));
}
//# sourceMappingURL=index.js.map