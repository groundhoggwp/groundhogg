"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _compose = require("@wordpress/compose");

var _blockMover = _interopRequireDefault(require("../block-mover"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockMobileToolbar(_ref) {
  var clientId = _ref.clientId;
  var isMobile = (0, _compose.useViewportMatch)('small', '<');

  if (!isMobile) {
    return null;
  }

  return (0, _element.createElement)("div", {
    className: "block-editor-block-mobile-toolbar"
  }, (0, _element.createElement)(_blockMover.default, {
    clientIds: [clientId]
  }));
}

var _default = BlockMobileToolbar;
exports.default = _default;
//# sourceMappingURL=index.js.map