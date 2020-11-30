import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { __experimentalUsePageTemplatePickerVisible, __experimentalUsePageTemplatePickerAvailable } from './use-page-template-picker';

var __experimentalWithPageTemplatePicker = createHigherOrderComponent(function (WrappedComponent) {
  return function (props) {
    var _props$modalLayoutPic;

    var isTemplatePickerEnabled = !((_props$modalLayoutPic = props.modalLayoutPicker) !== null && _props$modalLayoutPic !== void 0 ? _props$modalLayoutPic : false);

    var isTemplatePickerVisible = isTemplatePickerEnabled && __experimentalUsePageTemplatePickerVisible();

    var isTemplatePickerAvailable = isTemplatePickerEnabled && __experimentalUsePageTemplatePickerAvailable();

    return createElement(WrappedComponent, _extends({}, props, {
      isTemplatePickerVisible: isTemplatePickerVisible,
      isTemplatePickerAvailable: isTemplatePickerAvailable
    }));
  };
}, '__experimentalWithPageTemplatePicker');

export default __experimentalWithPageTemplatePicker;
//# sourceMappingURL=with-page-template-picker.native.js.map