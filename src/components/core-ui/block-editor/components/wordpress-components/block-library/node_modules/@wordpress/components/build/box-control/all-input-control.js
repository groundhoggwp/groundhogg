"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = AllInputControl;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _unitControl = _interopRequireDefault(require("./unit-control"));

var _utils = require("./utils");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function AllInputControl(_ref) {
  var _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? _lodash.noop : _ref$onChange,
      _ref$onFocus = _ref.onFocus,
      onFocus = _ref$onFocus === void 0 ? _lodash.noop : _ref$onFocus,
      _ref$onHoverOn = _ref.onHoverOn,
      onHoverOn = _ref$onHoverOn === void 0 ? _lodash.noop : _ref$onHoverOn,
      _ref$onHoverOff = _ref.onHoverOff,
      onHoverOff = _ref$onHoverOff === void 0 ? _lodash.noop : _ref$onHoverOff,
      values = _ref.values,
      props = (0, _objectWithoutProperties2.default)(_ref, ["onChange", "onFocus", "onHoverOn", "onHoverOff", "values"]);
  var allValue = (0, _utils.getAllValue)(values);
  var hasValues = (0, _utils.isValuesDefined)(values);
  var isMixed = hasValues && (0, _utils.isValuesMixed)(values);
  var allPlaceholder = isMixed ? _utils.LABELS.mixed : null;

  var handleOnFocus = function handleOnFocus(event) {
    onFocus(event, {
      side: 'all'
    });
  };

  var handleOnChange = function handleOnChange(next) {
    var nextValues = _objectSpread({}, values);

    nextValues.top = next;
    nextValues.bottom = next;
    nextValues.left = next;
    nextValues.right = next;
    onChange(nextValues);
  };

  var handleOnHoverOn = function handleOnHoverOn() {
    onHoverOn({
      top: true,
      bottom: true,
      left: true,
      right: true
    });
  };

  var handleOnHoverOff = function handleOnHoverOff() {
    onHoverOff({
      top: false,
      bottom: false,
      left: false,
      right: false
    });
  };

  return (0, _element.createElement)(_unitControl.default, (0, _extends2.default)({}, props, {
    disableUnits: isMixed,
    isOnly: true,
    value: allValue,
    onChange: handleOnChange,
    onFocus: handleOnFocus,
    onHoverOn: handleOnHoverOn,
    onHoverOff: handleOnHoverOff,
    placeholder: allPlaceholder
  }));
}
//# sourceMappingURL=all-input-control.js.map