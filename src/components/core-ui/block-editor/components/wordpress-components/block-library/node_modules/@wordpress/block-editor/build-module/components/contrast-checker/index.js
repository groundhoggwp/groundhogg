import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import tinycolor from 'tinycolor2';
/**
 * WordPress dependencies
 */

import { speak } from '@wordpress/a11y';
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { useEffect } from '@wordpress/element';

function ContrastCheckerMessage(_ref) {
  var tinyBackgroundColor = _ref.tinyBackgroundColor,
      tinyTextColor = _ref.tinyTextColor,
      backgroundColor = _ref.backgroundColor,
      textColor = _ref.textColor;
  var msg = tinyBackgroundColor.getBrightness() < tinyTextColor.getBrightness() ? __('This color combination may be hard for people to read. Try using a darker background color and/or a brighter text color.') : __('This color combination may be hard for people to read. Try using a brighter background color and/or a darker text color.'); // Note: The `Notice` component can speak messages via its `spokenMessage`
  // prop, but the contrast checker requires granular control over when the
  // announcements are made. Notably, the message will be re-announced if a
  // new color combination is selected and the contrast is still insufficient.

  useEffect(function () {
    speak(__('This color combination may be hard for people to read.'));
  }, [backgroundColor, textColor]);
  return createElement("div", {
    className: "block-editor-contrast-checker"
  }, createElement(Notice, {
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

  var tinyBackgroundColor = tinycolor(backgroundColor || fallbackBackgroundColor);
  var tinyTextColor = tinycolor(textColor || fallbackTextColor);
  var hasTransparency = tinyBackgroundColor.getAlpha() !== 1 || tinyTextColor.getAlpha() !== 1;

  if (hasTransparency || tinycolor.isReadable(tinyBackgroundColor, tinyTextColor, {
    level: 'AA',
    size: isLargeText || isLargeText !== false && fontSize >= 24 ? 'large' : 'small'
  })) {
    return null;
  }

  return createElement(ContrastCheckerMessage, {
    backgroundColor: backgroundColor,
    textColor: textColor,
    tinyBackgroundColor: tinyBackgroundColor,
    tinyTextColor: tinyTextColor
  });
}

export default ContrastChecker;
//# sourceMappingURL=index.js.map