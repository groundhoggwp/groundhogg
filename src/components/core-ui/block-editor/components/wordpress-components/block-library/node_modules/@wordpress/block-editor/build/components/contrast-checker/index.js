"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _tinycolor = _interopRequireDefault(require("tinycolor2"));

var _a11y = require("@wordpress/a11y");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function ContrastCheckerMessage(_ref) {
  var tinyBackgroundColor = _ref.tinyBackgroundColor,
      tinyTextColor = _ref.tinyTextColor,
      backgroundColor = _ref.backgroundColor,
      textColor = _ref.textColor;
  var msg = tinyBackgroundColor.getBrightness() < tinyTextColor.getBrightness() ? (0, _i18n.__)('This color combination may be hard for people to read. Try using a darker background color and/or a brighter text color.') : (0, _i18n.__)('This color combination may be hard for people to read. Try using a brighter background color and/or a darker text color.'); // Note: The `Notice` component can speak messages via its `spokenMessage`
  // prop, but the contrast checker requires granular control over when the
  // announcements are made. Notably, the message will be re-announced if a
  // new color combination is selected and the contrast is still insufficient.

  (0, _element.useEffect)(function () {
    (0, _a11y.speak)((0, _i18n.__)('This color combination may be hard for people to read.'));
  }, [backgroundColor, textColor]);
  return (0, _element.createElement)("div", {
    className: "block-editor-contrast-checker"
  }, (0, _element.createElement)(_components.Notice, {
    spokenMessage: null,
    status: "warning",
    isDismissible: false
  }, msg));
}

function ContrastChecker(_ref2) {
  var backgroundColor = _ref2.backgroundColor,
      fallbackBackgroundColor = _ref2.fallbackBackgroundColor,
      fallbackTextColor = _ref2.fallbackTextColor,
      fontSize = _ref2.fontSize,
      isLargeText = _ref2.isLargeText,
      textColor = _ref2.textColor;

  if (!(backgroundColor || fallbackBackgroundColor) || !(textColor || fallbackTextColor)) {
    return null;
  }

  var tinyBackgroundColor = (0, _tinycolor.default)(backgroundColor || fallbackBackgroundColor);
  var tinyTextColor = (0, _tinycolor.default)(textColor || fallbackTextColor);
  var hasTransparency = tinyBackgroundColor.getAlpha() !== 1 || tinyTextColor.getAlpha() !== 1;

  if (hasTransparency || _tinycolor.default.isReadable(tinyBackgroundColor, tinyTextColor, {
    level: 'AA',
    size: isLargeText || isLargeText !== false && fontSize >= 24 ? 'large' : 'small'
  })) {
    return null;
  }

  return (0, _element.createElement)(ContrastCheckerMessage, {
    backgroundColor: backgroundColor,
    textColor: textColor,
    tinyBackgroundColor: tinyBackgroundColor,
    tinyTextColor: tinyTextColor
  });
}

var _default = ContrastChecker;
exports.default = _default;
//# sourceMappingURL=index.js.map