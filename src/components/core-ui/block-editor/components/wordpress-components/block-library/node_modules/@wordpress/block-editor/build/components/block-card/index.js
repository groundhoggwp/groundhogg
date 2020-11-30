"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _blockIcon = _interopRequireDefault(require("../block-icon"));

/**
 * Internal dependencies
 */
function BlockCard(_ref) {
  var blockType = _ref.blockType;
  return (0, _element.createElement)("div", {
    className: "block-editor-block-card"
  }, (0, _element.createElement)(_blockIcon.default, {
    icon: blockType.icon,
    showColors: true
  }), (0, _element.createElement)("div", {
    className: "block-editor-block-card__content"
  }, (0, _element.createElement)("h2", {
    className: "block-editor-block-card__title"
  }, blockType.title), (0, _element.createElement)("span", {
    className: "block-editor-block-card__description"
  }, blockType.description)));
}

var _default = BlockCard;
exports.default = _default;
//# sourceMappingURL=index.js.map