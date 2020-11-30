"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
function MissingBlockWarning(_ref) {
  var attributes = _ref.attributes,
      convertToHTML = _ref.convertToHTML;
  var originalName = attributes.originalName,
      originalUndelimitedContent = attributes.originalUndelimitedContent;
  var hasContent = !!originalUndelimitedContent;
  var hasHTMLBlock = (0, _blocks.getBlockType)('core/html');
  var actions = [];
  var messageHTML;

  if (hasContent && hasHTMLBlock) {
    messageHTML = (0, _i18n.sprintf)(
    /* translators: %s: block name */
    (0, _i18n.__)('Your site doesn’t include support for the "%s" block. You can leave this block intact, convert its content to a Custom HTML block, or remove it entirely.'), originalName);
    actions.push((0, _element.createElement)(_components.Button, {
      key: "convert",
      onClick: convertToHTML,
      isPrimary: true
    }, (0, _i18n.__)('Keep as HTML')));
  } else {
    messageHTML = (0, _i18n.sprintf)(
    /* translators: %s: block name */
    (0, _i18n.__)('Your site doesn’t include support for the "%s" block. You can leave this block intact or remove it entirely.'), originalName);
  }

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.Warning, {
    actions: actions
  }, messageHTML), (0, _element.createElement)(_element.RawHTML, null, originalUndelimitedContent));
}

var MissingEdit = (0, _data.withDispatch)(function (dispatch, _ref2) {
  var clientId = _ref2.clientId,
      attributes = _ref2.attributes;

  var _dispatch = dispatch('core/block-editor'),
      replaceBlock = _dispatch.replaceBlock;

  return {
    convertToHTML: function convertToHTML() {
      replaceBlock(clientId, (0, _blocks.createBlock)('core/html', {
        content: attributes.originalUndelimitedContent
      }));
    }
  };
})(MissingBlockWarning);
var _default = MissingEdit;
exports.default = _default;
//# sourceMappingURL=edit.js.map