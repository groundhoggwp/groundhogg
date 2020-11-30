import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { createContext, useContext } from '@wordpress/element';
var GlobalStylesContext = createContext({
  style: {}
});
export var useGlobalStyles = function useGlobalStyles() {
  var globalStyles = useContext(GlobalStylesContext);
  return globalStyles;
};
export var withGlobalStyles = function withGlobalStyles(WrappedComponent) {
  return function (props) {
    return createElement(GlobalStylesContext.Consumer, null, function (globalStyles) {
      return createElement(WrappedComponent, _extends({}, props, {
        globalStyles: globalStyles
      }));
    });
  };
};
export default GlobalStylesContext;
//# sourceMappingURL=index.native.js.map