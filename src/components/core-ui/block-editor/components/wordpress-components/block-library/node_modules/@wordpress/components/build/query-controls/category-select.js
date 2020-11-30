"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = CategorySelect;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _terms = require("./terms");

var _treeSelect = _interopRequireDefault(require("../tree-select"));

/**
 * Internal dependencies
 */
function CategorySelect(_ref) {
  var label = _ref.label,
      noOptionLabel = _ref.noOptionLabel,
      categoriesList = _ref.categoriesList,
      selectedCategoryId = _ref.selectedCategoryId,
      onChange = _ref.onChange,
      props = (0, _objectWithoutProperties2.default)(_ref, ["label", "noOptionLabel", "categoriesList", "selectedCategoryId", "onChange"]);
  var termsTree = (0, _terms.buildTermsTree)(categoriesList);
  return (0, _element.createElement)(_treeSelect.default, (0, _extends2.default)({
    label: label,
    noOptionLabel: noOptionLabel,
    onChange: onChange
  }, {
    tree: termsTree,
    selectedId: selectedCategoryId
  }, props));
}
//# sourceMappingURL=category-select.js.map