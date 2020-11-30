"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _compose = require("@wordpress/compose");

var _registryProvider = require("../registry-provider");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Higher-order component which renders the original component with the current
 * registry context passed as its `registry` prop.
 *
 * @param {WPComponent} OriginalComponent Original component.
 *
 * @return {WPComponent} Enhanced component.
 */
var withRegistry = (0, _compose.createHigherOrderComponent)(function (OriginalComponent) {
  return function (props) {
    return (0, _element.createElement)(_registryProvider.RegistryConsumer, null, function (registry) {
      return (0, _element.createElement)(OriginalComponent, (0, _extends2.default)({}, props, {
        registry: registry
      }));
    });
  };
}, 'withRegistry');
var _default = withRegistry;
exports.default = _default;
//# sourceMappingURL=index.js.map