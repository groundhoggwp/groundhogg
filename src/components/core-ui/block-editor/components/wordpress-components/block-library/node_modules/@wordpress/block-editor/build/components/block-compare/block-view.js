"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var BlockView = function BlockView(_ref) {
  var title = _ref.title,
      rawContent = _ref.rawContent,
      renderedContent = _ref.renderedContent,
      action = _ref.action,
      actionText = _ref.actionText,
      className = _ref.className;
  return (0, _element.createElement)("div", {
    className: className
  }, (0, _element.createElement)("div", {
    className: "block-editor-block-compare__content"
  }, (0, _element.createElement)("h2", {
    className: "block-editor-block-compare__heading"
  }, title), (0, _element.createElement)("div", {
    className: "block-editor-block-compare__html"
  }, rawContent), (0, _element.createElement)("div", {
    className: "block-editor-block-compare__preview edit-post-visual-editor"
  }, renderedContent)), (0, _element.createElement)("div", {
    className: "block-editor-block-compare__action"
  }, (0, _element.createElement)(_components.Button, {
    isSecondary: true,
    tabIndex: "0",
    onClick: action
  }, actionText)));
};

var _default = BlockView;
exports.default = _default;
//# sourceMappingURL=block-view.js.map