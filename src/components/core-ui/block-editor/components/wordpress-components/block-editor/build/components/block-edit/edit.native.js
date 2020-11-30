"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.Edit = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

/**
 * WordPress dependencies
 */
var Edit = function Edit(props) {
  var name = props.name;
  var blockType = (0, _blocks.getBlockType)(name);

  if (!blockType) {
    return null;
  }

  var Component = blockType.edit;
  return (0, _element.createElement)(Component, props);
};

exports.Edit = Edit;

var _default = (0, _components.withFilters)('editor.BlockEdit')(Edit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map