"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Edit;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
var ALLOWED_BLOCKS = ['core/post-comment-content', 'core/post-comment-author']; // TODO: JSDOC types

function Edit(_ref) {
  var className = _ref.className,
      attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var commentId = attributes.commentId;

  var _useState = (0, _element.useState)(commentId),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      commentIdInput = _useState2[0],
      setCommentIdInput = _useState2[1];

  if (!commentId) {
    return (0, _element.createElement)(_components.Placeholder, {
      icon: _icons.blockDefault,
      label: (0, _i18n.__)('Post Comment'),
      instructions: (0, _i18n.__)('Input post comment ID')
    }, (0, _element.createElement)(_components.TextControl, {
      value: commentId,
      onChange: function onChange(val) {
        return setCommentIdInput(parseInt(val));
      }
    }), (0, _element.createElement)(_components.Button, {
      isPrimary: true,
      onClick: function onClick() {
        setAttributes({
          commentId: commentIdInput
        });
      }
    }, (0, _i18n.__)('Save')));
  }

  return (0, _element.createElement)("div", {
    className: className
  }, (0, _element.createElement)(_blockEditor.InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS
  }));
}
//# sourceMappingURL=edit.js.map