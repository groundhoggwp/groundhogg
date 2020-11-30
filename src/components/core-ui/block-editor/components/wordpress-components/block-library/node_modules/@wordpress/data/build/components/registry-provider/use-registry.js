"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useRegistry;

var _element = require("@wordpress/element");

var _context = require("./context");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * A custom react hook exposing the registry context for use.
 *
 * This exposes the `registry` value provided via the
 * <a href="#RegistryProvider">Registry Provider</a> to a component implementing
 * this hook.
 *
 * It acts similarly to the `useContext` react hook.
 *
 * Note: Generally speaking, `useRegistry` is a low level hook that in most cases
 * won't be needed for implementation. Most interactions with the wp.data api
 * can be performed via the `useSelect` hook,  or the `withSelect` and
 * `withDispatch` higher order components.
 *
 * @example
 * ```js
 * const {
 *   RegistryProvider,
 *   createRegistry,
 *   useRegistry,
 * } = wp.data
 *
 * const registry = createRegistry( {} );
 *
 * const SomeChildUsingRegistry = ( props ) => {
 *   const registry = useRegistry( registry );
 *   // ...logic implementing the registry in other react hooks.
 * };
 *
 *
 * const ParentProvidingRegistry = ( props ) => {
 *   return <RegistryProvider value={ registry }>
 *     <SomeChildUsingRegistry { ...props } />
 *   </RegistryProvider>
 * };
 * ```
 *
 * @return {Function}  A custom react hook exposing the registry context value.
 */
function useRegistry() {
  return (0, _element.useContext)(_context.Context);
}
//# sourceMappingURL=use-registry.js.map