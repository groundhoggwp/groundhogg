"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = AlignmentMatrixControl;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _reakit = require("reakit");

var _i18n = require("@wordpress/i18n");

var _compose = require("@wordpress/compose");

var _cell = _interopRequireDefault(require("./cell"));

var _alignmentMatrixControlStyles = require("./styles/alignment-matrix-control-styles");

var _rtl = require("../utils/rtl");

var _icon = _interopRequireDefault(require("./icon"));

var _utils = require("./utils");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function useBaseId(id) {
  var instanceId = (0, _compose.useInstanceId)(AlignmentMatrixControl, 'alignment-matrix-control');
  return id || instanceId;
}

function AlignmentMatrixControl(_ref) {
  var className = _ref.className,
      id = _ref.id,
      _ref$label = _ref.label,
      label = _ref$label === void 0 ? (0, _i18n.__)('Alignment Matrix Control') : _ref$label,
      _ref$defaultValue = _ref.defaultValue,
      defaultValue = _ref$defaultValue === void 0 ? 'center center' : _ref$defaultValue,
      value = _ref.value,
      _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? _lodash.noop : _ref$onChange,
      _ref$width = _ref.width,
      width = _ref$width === void 0 ? 92 : _ref$width,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "id", "label", "defaultValue", "value", "onChange", "width"]);

  var _useState = (0, _element.useState)(value !== null && value !== void 0 ? value : defaultValue),
      _useState2 = (0, _slicedToArray2.default)(_useState, 1),
      immutableDefaultValue = _useState2[0];

  var isRTL = (0, _rtl.useRTL)();
  var baseId = useBaseId(id);
  var initialCurrentId = (0, _utils.getItemId)(baseId, immutableDefaultValue);
  var composite = (0, _reakit.useCompositeState)({
    baseId: baseId,
    currentId: initialCurrentId,
    rtl: isRTL
  });

  var handleOnChange = function handleOnChange(nextValue) {
    onChange(nextValue);
  };

  (0, _element.useEffect)(function () {
    if (typeof value !== 'undefined') {
      composite.setCurrentId((0, _utils.getItemId)(baseId, value));
    }
  }, [value, composite.setCurrentId]);
  var classes = (0, _classnames.default)('component-alignment-matrix-control', className);
  return (0, _element.createElement)(_reakit.Composite, (0, _extends2.default)({}, props, composite, {
    "aria-label": label,
    as: _alignmentMatrixControlStyles.Root,
    className: classes,
    role: "grid",
    width: width
  }), _utils.GRID.map(function (cells, index) {
    return (0, _element.createElement)(_reakit.CompositeGroup, (0, _extends2.default)({}, composite, {
      as: _alignmentMatrixControlStyles.Row,
      role: "row",
      key: index
    }), cells.map(function (cell) {
      var cellId = (0, _utils.getItemId)(baseId, cell);
      var isActive = composite.currentId === cellId;
      return (0, _element.createElement)(_cell.default, (0, _extends2.default)({}, composite, {
        id: cellId,
        isActive: isActive,
        key: cell,
        value: cell,
        onFocus: function onFocus() {
          return handleOnChange(cell);
        }
      }));
    }));
  }));
}

AlignmentMatrixControl.Icon = _icon.default;
//# sourceMappingURL=index.js.map