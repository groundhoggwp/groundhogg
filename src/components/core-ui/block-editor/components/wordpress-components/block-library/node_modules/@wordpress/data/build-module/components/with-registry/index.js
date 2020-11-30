import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { RegistryConsumer } from '../registry-provider';
/**
 * Higher-order component which renders the original component with the current
 * registry context passed as its `registry` prop.
 *
 * @param {WPComponent} OriginalComponent Original component.
 *
 * @return {WPComponent} Enhanced component.
 */

var withRegistry = createHigherOrderComponent(function (OriginalComponent) {
  return function (props) {
    return createElement(RegistryConsumer, null, function (registry) {
      return createElement(OriginalComponent, _extends({}, props, {
        registry: registry
      }));
    });
  };
}, 'withRegistry');
export default withRegistry;
//# sourceMappingURL=index.js.map