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
function InserterPanel(_ref) {
  var title = _ref.title,
      icon = _ref.icon,
      children = _ref.children;
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)("div", {
    className: "block-editor-inserter__panel-header"
  }, (0, _element.createElement)("h2", {
    className: "block-editor-inserter__panel-title"
  }, title), (0, _element.createElement)(_components.Icon, {
    icon: icon
  })), (0, _element.createElement)("div", {
    className: "block-editor-inserter__panel-content"
  }, children));
}

var _default = InserterPanel;
exports.default = _default;
//# sourceMappingURL=panel.js.map