"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LiveBlockPreview;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _blockList = _interopRequireDefault(require("../block-list"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function LiveBlockPreview(_ref) {
  var onClick = _ref.onClick;
  return (0, _element.createElement)("div", {
    tabIndex: 0,
    role: "button",
    onClick: onClick,
    onKeyPress: onClick
  }, (0, _element.createElement)(_components.Disabled, null, (0, _element.createElement)(_blockList.default, null)));
}
//# sourceMappingURL=live.js.map