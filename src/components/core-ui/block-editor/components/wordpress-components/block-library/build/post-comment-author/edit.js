"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Edit;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

/**
 * WordPress dependencies
 */
function Edit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context;
  var className = attributes.className;
  var commentId = context.commentId;
  var displayName = (0, _data.useSelect)(function (select) {
    var _select = select('core'),
        getEntityRecord = _select.getEntityRecord;

    var comment = getEntityRecord('root', 'comment', commentId);
    var authorName = comment === null || comment === void 0 ? void 0 : comment.author_name; // eslint-disable-line camelcase

    if (comment && !authorName) {
      var _user$name;

      var user = getEntityRecord('root', 'user', comment.author);
      return (_user$name = user === null || user === void 0 ? void 0 : user.name) !== null && _user$name !== void 0 ? _user$name : (0, _i18n.__)('Anonymous');
    }

    return authorName !== null && authorName !== void 0 ? authorName : '';
  });
  return (0, _element.createElement)("p", {
    className: className
  }, displayName);
}
//# sourceMappingURL=edit.js.map