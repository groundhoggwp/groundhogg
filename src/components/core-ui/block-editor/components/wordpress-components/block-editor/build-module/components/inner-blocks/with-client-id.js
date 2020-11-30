import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { useBlockEditContext } from '../block-edit/context';
var withClientId = createHigherOrderComponent(function (WrappedComponent) {
  return function (props) {
    var _useBlockEditContext = useBlockEditContext(),
        clientId = _useBlockEditContext.clientId;

    return createElement(WrappedComponent, _extends({}, props, {
      clientId: clientId
    }));
  };
}, 'withClientId');
export default withClientId;
//# sourceMappingURL=with-client-id.js.map