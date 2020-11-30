"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ColorPalette;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _tinycolor = _interopRequireDefault(require("tinycolor2"));

var _i18n = require("@wordpress/i18n");

var _colorPicker = _interopRequireDefault(require("../color-picker"));

var _circularOptionPicker = _interopRequireDefault(require("../circular-option-picker"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ColorPalette(_ref) {
  var _ref$clearable = _ref.clearable,
      clearable = _ref$clearable === void 0 ? true : _ref$clearable,
      className = _ref.className,
      colors = _ref.colors,
      _ref$disableCustomCol = _ref.disableCustomColors,
      disableCustomColors = _ref$disableCustomCol === void 0 ? false : _ref$disableCustomCol,
      onChange = _ref.onChange,
      value = _ref.value;
  var clearColor = (0, _element.useCallback)(function () {
    return onChange(undefined);
  }, [onChange]);
  var colorOptions = (0, _element.useMemo)(function () {
    return (0, _lodash.map)(colors, function (_ref2) {
      var color = _ref2.color,
          name = _ref2.name;
      return (0, _element.createElement)(_circularOptionPicker.default.Option, {
        key: color,
        isSelected: value === color,
        selectedIconProps: value === color ? {
          fill: _tinycolor.default.mostReadable(color, ['#000', '#fff']).toHexString()
        } : {},
        tooltipText: name || // translators: %s: color hex code e.g: "#f00".
        (0, _i18n.sprintf)((0, _i18n.__)('Color code: %s'), color),
        style: {
          backgroundColor: color,
          color: color
        },
        onClick: value === color ? clearColor : function () {
          return onChange(color);
        },
        "aria-label": name ? // translators: %s: The name of the color e.g: "vivid red".
        (0, _i18n.sprintf)((0, _i18n.__)('Color: %s'), name) : // translators: %s: color hex code e.g: "#f00".
        (0, _i18n.sprintf)((0, _i18n.__)('Color code: %s'), color)
      });
    });
  }, [colors, value, onChange, clearColor]);

  var renderCustomColorPicker = function renderCustomColorPicker() {
    return (0, _element.createElement)(_colorPicker.default, {
      color: value,
      onChangeComplete: function onChangeComplete(color) {
        return onChange(color.hex);
      },
      disableAlpha: true
    });
  };

  return (0, _element.createElement)(_circularOptionPicker.default, {
    className: className,
    options: colorOptions,
    actions: (0, _element.createElement)(_element.Fragment, null, !disableCustomColors && (0, _element.createElement)(_circularOptionPicker.default.DropdownLinkAction, {
      dropdownProps: {
        renderContent: renderCustomColorPicker,
        contentClassName: 'components-color-palette__picker'
      },
      buttonProps: {
        'aria-label': (0, _i18n.__)('Custom color picker')
      },
      linkText: (0, _i18n.__)('Custom color')
    }), !!clearable && (0, _element.createElement)(_circularOptionPicker.default.ButtonAction, {
      onClick: clearColor
    }, (0, _i18n.__)('Clear')))
  });
}
//# sourceMappingURL=index.js.map