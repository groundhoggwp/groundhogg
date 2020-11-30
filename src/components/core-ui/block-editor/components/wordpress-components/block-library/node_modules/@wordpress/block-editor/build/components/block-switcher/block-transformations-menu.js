"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

var _blockIcon = _interopRequireDefault(require("../block-icon"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var BlockTransformationsMenu = function BlockTransformationsMenu(_ref) {
  var className = _ref.className,
      possibleBlockTransformations = _ref.possibleBlockTransformations,
      onSelect = _ref.onSelect;
  return (0, _element.createElement)(_components.MenuGroup, {
    label: (0, _i18n.__)('Transform to'),
    className: className
  }, possibleBlockTransformations.map(function (item) {
    var name = item.name,
        icon = item.icon,
        title = item.title;
    return (0, _element.createElement)(_components.MenuItem, {
      key: name,
      className: (0, _blocks.getBlockMenuDefaultClassName)(name),
      onClick: function onClick(event) {
        event.preventDefault();
        onSelect(name);
      }
    }, (0, _element.createElement)(_blockIcon.default, {
      icon: icon,
      showColors: true
    }), title);
  }));
};

var _default = BlockTransformationsMenu;
exports.default = _default;
//# sourceMappingURL=block-transformations-menu.js.map