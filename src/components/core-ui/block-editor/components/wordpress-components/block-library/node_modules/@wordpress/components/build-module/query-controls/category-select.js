import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import { buildTermsTree } from './terms';
import TreeSelect from '../tree-select';
export default function CategorySelect(_ref) {
  var label = _ref.label,
      noOptionLabel = _ref.noOptionLabel,
      categoriesList = _ref.categoriesList,
      selectedCategoryId = _ref.selectedCategoryId,
      onChange = _ref.onChange,
      props = _objectWithoutProperties(_ref, ["label", "noOptionLabel", "categoriesList", "selectedCategoryId", "onChange"]);

  var termsTree = buildTermsTree(categoriesList);
  return createElement(TreeSelect, _extends({
    label: label,
    noOptionLabel: noOptionLabel,
    onChange: onChange
  }, {
    tree: termsTree,
    selectedId: selectedCategoryId
  }, props));
}
//# sourceMappingURL=category-select.js.map