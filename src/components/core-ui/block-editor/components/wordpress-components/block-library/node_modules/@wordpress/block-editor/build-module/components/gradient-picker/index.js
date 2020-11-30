import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __experimentalGradientPicker as GradientPicker } from '@wordpress/components';
/**
 * Internal dependencies
 */

import useEditorFeature from '../use-editor-feature';
var EMPTY_ARRAY = [];

function GradientPickerWithGradients(props) {
  var gradients = useEditorFeature('color.gradients') || EMPTY_ARRAY;
  var disableCustomGradients = !useEditorFeature('color.customGradient');
  return createElement(GradientPicker, _extends({
    gradients: props.gradients !== undefined ? props.gradient : gradients,
    disableCustomGradients: props.disableCustomGradients !== undefined ? props.disableCustomGradients : disableCustomGradients
  }, props));
}

export default function (props) {
  var ComponentToUse = props.gradients !== undefined && props.disableCustomGradients !== undefined ? GradientPicker : GradientPickerWithGradients;
  return createElement(ComponentToUse, props);
}
//# sourceMappingURL=index.js.map