"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = TreeSelect;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _ = require("../");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function getSelectOptions(tree) {
  var level = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  return (0, _lodash.flatMap)(tree, function (treeNode) {
    return [{
      value: treeNode.id,
      label: (0, _lodash.repeat)("\xA0", level * 3) + (0, _lodash.unescape)(treeNode.name)
    }].concat((0, _toConsumableArray2.default)(getSelectOptions(treeNode.children || [], level + 1)));
  });
}

function TreeSelect(_ref) {
  var label = _ref.label,
      noOptionLabel = _ref.noOptionLabel,
      onChange = _ref.onChange,
      selectedId = _ref.selectedId,
      tree = _ref.tree,
      props = (0, _objectWithoutProperties2.default)(_ref, ["label", "noOptionLabel", "onChange", "selectedId", "tree"]);
  var options = (0, _lodash.compact)([noOptionLabel && {
    value: '',
    label: noOptionLabel
  }].concat((0, _toConsumableArray2.default)(getSelectOptions(tree))));
  return (0, _element.createElement)(_.SelectControl, (0, _extends2.default)({
    label: label,
    options: options,
    onChange: onChange
  }, {
    value: selectedId
  }, props));
}
//# sourceMappingURL=index.js.map