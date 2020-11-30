"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _store = require("../../store");

var _middlewares = _interopRequireDefault(require("../../store/middlewares"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var withRegistryProvider = (0, _compose.createHigherOrderComponent)(function (WrappedComponent) {
  return (0, _data.withRegistry)(function (_ref) {
    var _ref$useSubRegistry = _ref.useSubRegistry,
        useSubRegistry = _ref$useSubRegistry === void 0 ? true : _ref$useSubRegistry,
        registry = _ref.registry,
        props = (0, _objectWithoutProperties2.default)(_ref, ["useSubRegistry", "registry"]);

    if (!useSubRegistry) {
      return (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({
        registry: registry
      }, props));
    }

    var _useState = (0, _element.useState)(null),
        _useState2 = (0, _slicedToArray2.default)(_useState, 2),
        subRegistry = _useState2[0],
        setSubRegistry = _useState2[1];

    (0, _element.useEffect)(function () {
      var newRegistry = (0, _data.createRegistry)({}, registry);
      var store = newRegistry.registerStore('core/block-editor', _store.storeConfig); // This should be removed after the refactoring of the effects to controls.

      (0, _middlewares.default)(store);
      setSubRegistry(newRegistry);
    }, [registry]);

    if (!subRegistry) {
      return null;
    }

    return (0, _element.createElement)(_data.RegistryProvider, {
      value: subRegistry
    }, (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({
      registry: subRegistry
    }, props)));
  });
}, 'withRegistryProvider');
var _default = withRegistryProvider;
exports.default = _default;
//# sourceMappingURL=with-registry-provider.js.map