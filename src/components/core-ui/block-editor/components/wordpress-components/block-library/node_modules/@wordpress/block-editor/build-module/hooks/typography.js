import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { hasBlockSupport } from '@wordpress/blocks';
import { PanelBody } from '@wordpress/components';
import { Platform } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import InspectorControls from '../components/inspector-controls';
import { LINE_HEIGHT_SUPPORT_KEY, LineHeightEdit, useIsLineHeightDisabled } from './line-height';
import { FONT_SIZE_SUPPORT_KEY, FontSizeEdit, useIsFontSizeDisabled } from './font-size';
export var TYPOGRAPHY_SUPPORT_KEYS = [LINE_HEIGHT_SUPPORT_KEY, FONT_SIZE_SUPPORT_KEY];
export function TypographyPanel(props) {
  var isDisabled = useIsTypographyDisabled(props);
  var isSupported = hasTypographySupport(props.name);
  if (isDisabled || !isSupported) return null;
  return createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Typography')
  }, createElement(FontSizeEdit, props), createElement(LineHeightEdit, props)));
}

var hasTypographySupport = function hasTypographySupport(blockName) {
  return Platform.OS === 'web' && TYPOGRAPHY_SUPPORT_KEYS.some(function (key) {
    return hasBlockSupport(blockName, key);
  });
};

function useIsTypographyDisabled() {
  var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var configs = [useIsFontSizeDisabled(props), useIsLineHeightDisabled(props)];
  return configs.filter(Boolean).length === configs.length;
}
//# sourceMappingURL=typography.js.map