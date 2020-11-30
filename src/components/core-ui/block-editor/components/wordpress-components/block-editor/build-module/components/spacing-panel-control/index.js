import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';
/**
 * Internal dependencies
 */

import InspectorControls from '../inspector-controls';
import useEditorFeature from '../use-editor-feature';
export default function SpacingPanelControl(_ref) {
  var children = _ref.children,
      props = _objectWithoutProperties(_ref, ["children"]);

  var isSpacingEnabled = useEditorFeature('spacing.customPadding');
  if (!isSpacingEnabled) return null;
  return createElement(InspectorControls, props, createElement(PanelBody, {
    title: __('Spacing')
  }, children));
}
//# sourceMappingURL=index.js.map