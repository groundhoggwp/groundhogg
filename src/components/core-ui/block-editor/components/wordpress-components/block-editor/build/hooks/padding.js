"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.PaddingEdit = PaddingEdit;
exports.PADDING_SUPPORT_KEY = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

var _utils = require("./utils");

var _unitControl = require("../components/unit-control");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var PADDING_SUPPORT_KEY = '__experimentalPadding';
/**
 * Inspector control panel containing the line height related configuration
 *
 * @param {Object} props
 *
 * @return {WPElement} Line height edit element.
 */

exports.PADDING_SUPPORT_KEY = PADDING_SUPPORT_KEY;

function PaddingEdit(props) {
  var _style$spacing;

  var blockName = props.name,
      style = props.attributes.style,
      setAttributes = props.setAttributes;
  var units = (0, _unitControl.useCustomUnits)();

  if (!(0, _blocks.hasBlockSupport)(blockName, PADDING_SUPPORT_KEY)) {
    return null;
  }

  var onChange = function onChange(next) {
    var newStyle = _objectSpread(_objectSpread({}, style), {}, {
      spacing: {
        padding: next
      }
    });

    setAttributes({
      style: (0, _utils.cleanEmptyObject)(newStyle)
    });
  };

  var onChangeShowVisualizer = function onChangeShowVisualizer(next) {
    var newStyle = _objectSpread(_objectSpread({}, style), {}, {
      visualizers: {
        padding: next
      }
    });

    setAttributes({
      style: (0, _utils.cleanEmptyObject)(newStyle)
    });
  };

  return _element.Platform.select({
    web: (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.__experimentalBoxControl, {
      values: style === null || style === void 0 ? void 0 : (_style$spacing = style.spacing) === null || _style$spacing === void 0 ? void 0 : _style$spacing.padding,
      onChange: onChange,
      onChangeShowVisualizer: onChangeShowVisualizer,
      label: (0, _i18n.__)('Padding'),
      units: units
    })),
    native: null
  });
}
//# sourceMappingURL=padding.js.map