"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostContentEdit;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _innerBlocks = _interopRequireDefault(require("./inner-blocks"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function PostContentEdit(_ref) {
  var _ref$context = _ref.context,
      contextPostId = _ref$context.postId,
      contextPostType = _ref$context.postType;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select$getCurrentPos;

    return (_select$getCurrentPos = select('core/editor').getCurrentPost()) !== null && _select$getCurrentPos !== void 0 ? _select$getCurrentPos : {};
  }),
      currentPostId = _useSelect.id,
      currentPostType = _useSelect.type; // Only render InnerBlocks if the context is different from the active post
  // to avoid infinite recursion of post content.


  if (contextPostId && contextPostType && contextPostId !== currentPostId && contextPostType !== currentPostType) {
    return (0, _element.createElement)(_innerBlocks.default, {
      postType: contextPostType,
      postId: contextPostId
    });
  }

  return (0, _element.createElement)("div", {
    className: "wp-block-post-content__placeholder"
  }, (0, _element.createElement)("span", null, (0, _i18n.__)('This is a placeholder for post content.')));
}
//# sourceMappingURL=edit.js.map