"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = CodeEdit;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
function CodeEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();
  return (0, _element.createElement)("pre", blockWrapperProps, (0, _element.createElement)(_blockEditor.RichText, {
    tagName: "code",
    value: attributes.content,
    onChange: function onChange(content) {
      return setAttributes({
        content: content
      });
    },
    placeholder: (0, _i18n.__)('Write code…'),
    "aria-label": (0, _i18n.__)('Code')
  }));
}
//# sourceMappingURL=edit.js.map