"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Cell;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _Composite = require("reakit/Composite");

var _tooltip = _interopRequireDefault(require("../tooltip"));

var _visuallyHidden = _interopRequireDefault(require("../visually-hidden"));

var _utils = require("./utils");

var _alignmentMatrixControlStyles = require("./styles/alignment-matrix-control-styles");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Internal dependencies
 */
function Cell(_ref) {
  var _ref$isActive = _ref.isActive,
      isActive = _ref$isActive === void 0 ? false : _ref$isActive,
      value = _ref.value,
      props = (0, _objectWithoutProperties2.default)(_ref, ["isActive", "value"]);
  var tooltipText = _utils.ALIGNMENT_LABEL[value];
  return (0, _element.createElement)(_tooltip.default, {
    text: tooltipText
  }, (0, _element.createElement)(_Composite.CompositeItem, (0, _extends2.default)({
    as: _alignmentMatrixControlStyles.Cell,
    role: "gridcell"
  }, props), (0, _element.createElement)(_visuallyHidden.default, null, value), (0, _element.createElement)(_alignmentMatrixControlStyles.Point, {
    isActive: isActive,
    role: "presentation"
  })));
}
//# sourceMappingURL=cell.js.map