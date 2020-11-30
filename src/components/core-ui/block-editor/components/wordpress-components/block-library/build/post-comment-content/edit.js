"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Edit;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _coreData = require("@wordpress/core-data");

/**
 * WordPress dependencies
 */
// TODO: JSDOC types
function Edit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context;
  var className = attributes.className;
  var commentId = context.commentId;

  var _useEntityProp = (0, _coreData.useEntityProp)('root', 'comment', 'content', commentId),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 1),
      content = _useEntityProp2[0];

  return (0, _element.createElement)("p", {
    className: className
  }, content);
}
//# sourceMappingURL=edit.js.map