"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BoxInputControls;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _unitControl = _interopRequireDefault(require("./unit-control"));

var _utils = require("./utils");

var _boxControlStyles = require("./styles/box-control-styles");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function BoxInputControls(_ref) {
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

  var createHandleOnFocus = function createHandleOnFocus(side) {
    return function (event) {
      onFocus(event, {
        side: side
      });
    };
  };

  var createHandleOnHoverOn = function createHandleOnHoverOn(side) {
    return function () {
      onHoverOn((0, _defineProperty2.default)({}, side, true));
    };
  };

  var createHandleOnHoverOff = function createHandleOnHoverOff(side) {
    return function () {
      onHoverOff((0, _defineProperty2.default)({}, side, false));
    };
  };

  var handleOnChange = function handleOnChange(nextValues) {
    onChange(nextValues);
  };

  var top = values.top,
      right = values.right,
      bottom = values.bottom,
      left = values.left;

  var createHandleOnChange = function createHandleOnChange(side) {
    return function (next, _ref2) {
      var event = _ref2.event;
      var altKey = event.altKey;

      var nextValues = _objectSpread({}, values);

      nextValues[side] = next;
      /**
       * Supports changing pair sides. For example, holding the ALT key
       * when changing the TOP will also update BOTTOM.
       */

      if (altKey) {
        switch (side) {
          case 'top':
            nextValues.bottom = next;
            break;

          case 'bottom':
            nextValues.top = next;
            break;

          case 'left':
            nextValues.right = next;
            break;

          case 'right':
            nextValues.left = next;
            break;
        }
      }

      handleOnChange(nextValues);
    };
  };

  return (0, _element.createElement)(_boxControlStyles.LayoutContainer, {
    className: "component-box-control__input-controls-wrapper"
  }, (0, _element.createElement)(_boxControlStyles.Layout, {
    gap: 0,
    align: "top",
    className: "component-box-control__input-controls"
  }, (0, _element.createElement)(_unitControl.default, (0, _extends2.default)({}, props, {
    isFirst: true,
    value: top,
    onChange: createHandleOnChange('top'),
    onFocus: createHandleOnFocus('top'),
    onHoverOn: createHandleOnHoverOn('top'),
    onHoverOff: createHandleOnHoverOff('top'),
    label: _utils.LABELS.top
  })), (0, _element.createElement)(_unitControl.default, (0, _extends2.default)({}, props, {
    value: right,
    onChange: createHandleOnChange('right'),
    onFocus: createHandleOnFocus('right'),
    onHoverOn: createHandleOnHoverOn('right'),
    onHoverOff: createHandleOnHoverOff('right'),
    label: _utils.LABELS.right
  })), (0, _element.createElement)(_unitControl.default, (0, _extends2.default)({}, props, {
    value: bottom,
    onChange: createHandleOnChange('bottom'),
    onFocus: createHandleOnFocus('bottom'),
    onHoverOn: createHandleOnHoverOn('bottom'),
    onHoverOff: createHandleOnHoverOff('bottom'),
    label: _utils.LABELS.bottom
  })), (0, _element.createElement)(_unitControl.default, (0, _extends2.default)({}, props, {
    isLast: true,
    value: left,
    onChange: createHandleOnChange('left'),
    onFocus: createHandleOnFocus('left'),
    onHoverOn: createHandleOnHoverOn('left'),
    onHoverOff: createHandleOnHoverOff('left'),
    label: _utils.LABELS.left
  }))));
}
//# sourceMappingURL=input-controls.js.map