import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { withRegistry, createRegistry, RegistryProvider } from '@wordpress/data';
import { createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { storeConfig } from '../../store';
import applyMiddlewares from '../../store/middlewares';
var withRegistryProvider = createHigherOrderComponent(function (WrappedComponent) {
  return withRegistry(function (_ref) {
    var _ref$useSubRegistry = _ref.useSubRegistry,
        useSubRegistry = _ref$useSubRegistry === void 0 ? true : _ref$useSubRegistry,
        registry = _ref.registry,
        props = _objectWithoutProperties(_ref, ["useSubRegistry", "registry"]);

    if (!useSubRegistry) {
      return createElement(WrappedComponent, _extends({
        registry: registry
      }, props));
    }

    var _useState = useState(null),
        _useState2 = _slicedToArray(_useState, 2),
        subRegistry = _useState2[0],
        setSubRegistry = _useState2[1];

    useEffect(function () {
      var newRegistry = createRegistry({}, registry);
      var store = newRegistry.registerStore('core/block-editor', storeConfig); // This should be removed after the refactoring of the effects to controls.

      applyMiddlewares(store);
      setSubRegistry(newRegistry);
    }, [registry]);

    if (!subRegistry) {
      return null;
    }

    return createElement(RegistryProvider, {
      value: subRegistry
    }, createElement(WrappedComponent, _extends({
      registry: subRegistry
    }, props)));
  });
}, 'withRegistryProvider');
export default withRegistryProvider;
//# sourceMappingURL=with-registry-provider.js.map