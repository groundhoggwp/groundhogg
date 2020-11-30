"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.render = void 0;

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _hooks = require("@wordpress/hooks");

var _react = require("./react");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var render = function render(element, id) {
  return _reactNative.AppRegistry.registerComponent(id, function () {
    return function (propsFromParent) {
      var parentProps = (0, _lodash.omit)(propsFromParent || {}, ['rootTag']);
      (0, _hooks.doAction)('native.pre-render', parentProps);
      var filteredProps = (0, _hooks.applyFilters)('native.block_editor_props', parentProps);
      (0, _hooks.doAction)('native.render', filteredProps);
      return (0, _react.cloneElement)(element, filteredProps);
    };
  });
};
/**
 * Render a given element on Native.
 * This actually returns a componentProvider that can be registered with `AppRegistry.registerComponent`
 *
 * @param {WPElement}   element Element to render.
 */


exports.render = render;
//# sourceMappingURL=react-platform.native.js.map