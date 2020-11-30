import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { unescape as unescapeString, repeat, flatMap, compact } from 'lodash';
/**
 * Internal dependencies
 */

import { SelectControl } from '../';

function getSelectOptions(tree) {
  var level = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  return flatMap(tree, function (treeNode) {
    return [{
      value: treeNode.id,
      label: repeat("\xA0", level * 3) + unescapeString(treeNode.name)
    }].concat(_toConsumableArray(getSelectOptions(treeNode.children || [], level + 1)));
  });
}

export default function TreeSelect(_ref) {
  var label = _ref.label,
      noOptionLabel = _ref.noOptionLabel,
      onChange = _ref.onChange,
      selectedId = _ref.selectedId,
      tree = _ref.tree,
      props = _objectWithoutProperties(_ref, ["label", "noOptionLabel", "onChange", "selectedId", "tree"]);

  var options = compact([noOptionLabel && {
    value: '',
    label: noOptionLabel
  }].concat(_toConsumableArray(getSelectOptions(tree))));
  return createElement(SelectControl, _extends({
    label: label,
    options: options,
    onChange: onChange
  }, {
    value: selectedId
  }, props));
}
//# sourceMappingURL=index.js.map