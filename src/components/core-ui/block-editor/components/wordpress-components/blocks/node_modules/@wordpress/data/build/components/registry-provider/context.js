"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.RegistryConsumer = exports.Context = void 0;

var _element = require("@wordpress/element");

var _defaultRegistry = _interopRequireDefault(require("../../default-registry"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var Context = (0, _element.createContext)(_defaultRegistry.default);
exports.Context = Context;
var Consumer = Context.Consumer,
    Provider = Context.Provider;
/**
 * A custom react Context consumer exposing the provided `registry` to
 * children components. Used along with the RegistryProvider.
 *
 * You can read more about the react context api here:
 * https://reactjs.org/docs/context.html#contextprovider
 *
 * @example
 * ```js
 * const {
 *   RegistryProvider,
 *   RegistryConsumer,
 *   createRegistry
 * } = wp.data;
 *
 * const registry = createRegistry( {} );
 *
 * const App = ( { props } ) => {
 *   return <RegistryProvider value={ registry }>
 *     <div>Hello There</div>
 *     <RegistryConsumer>
 *       { ( registry ) => (
 *         <ComponentUsingRegistry
 *         		{ ...props }
 *         	  registry={ registry }
 *       ) }
 *     </RegistryConsumer>
 *   </RegistryProvider>
 * }
 * ```
 */

var RegistryConsumer = Consumer;
/**
 * A custom Context provider for exposing the provided `registry` to children
 * components via a consumer.
 *
 * See <a name="#RegistryConsumer">RegistryConsumer</a> documentation for
 * example.
 */

exports.RegistryConsumer = RegistryConsumer;
var _default = Provider;
exports.default = _default;
//# sourceMappingURL=context.js.map