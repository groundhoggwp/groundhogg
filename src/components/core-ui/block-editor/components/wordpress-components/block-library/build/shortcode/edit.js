"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ShortcodeEdit;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

/**
 * WordPress dependencies
 */
function ShortcodeEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var instanceId = (0, _compose.useInstanceId)(ShortcodeEdit);
  var inputId = "blocks-shortcode-input-".concat(instanceId);
  return (0, _element.createElement)("div", {
    className: "wp-block-shortcode components-placeholder"
  }, (0, _element.createElement)("label", {
    htmlFor: inputId,
    className: "components-placeholder__label"
  }, (0, _element.createElement)(_icons.Icon, {
    icon: _icons.shortcode
  }), (0, _i18n.__)('Shortcode')), (0, _element.createElement)(_blockEditor.PlainText, {
    className: "blocks-shortcode__textarea",
    id: inputId,
    value: attributes.text,
    placeholder: (0, _i18n.__)('Write shortcode here…'),
    onChange: function onChange(text) {
      return setAttributes({
        text: text
      });
    }
  }));
}
//# sourceMappingURL=edit.js.map