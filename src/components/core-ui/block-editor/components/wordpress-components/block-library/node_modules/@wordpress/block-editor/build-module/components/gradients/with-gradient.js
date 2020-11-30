import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import { __experimentalUseGradient } from './use-gradient';
export var withGradient = function withGradient(WrappedComponent) {
  return function (props) {
    var _experimentalUseGrad = __experimentalUseGradient(),
        gradientValue = _experimentalUseGrad.gradientValue;

    return createElement(WrappedComponent, _extends({}, props, {
      gradientValue: gradientValue
    }));
  };
};
//# sourceMappingURL=with-gradient.js.map