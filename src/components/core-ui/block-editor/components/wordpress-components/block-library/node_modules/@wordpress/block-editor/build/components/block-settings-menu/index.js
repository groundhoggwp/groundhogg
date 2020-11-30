"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockSettingsMenu = BlockSettingsMenu;
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _components = require("@wordpress/components");

var _blockSettingsDropdown = _interopRequireDefault(require("./block-settings-dropdown"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockSettingsMenu(_ref) {
  var clientIds = _ref.clientIds,
      props = (0, _objectWithoutProperties2.default)(_ref, ["clientIds"]);
  return (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarItem, null, function (toggleProps) {
    return (0, _element.createElement)(_blockSettingsDropdown.default, (0, _extends2.default)({
      clientIds: clientIds,
      toggleProps: toggleProps
    }, props));
  }));
}

var _default = BlockSettingsMenu;
exports.default = _default;
//# sourceMappingURL=index.js.map