"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = FormatEdit;

var _element = require("@wordpress/element");

var _getActiveFormat = require("../get-active-format");

var _getActiveObject = require("../get-active-object");

/**
 * Internal dependencies
 */

/**
 * Set of all interactive content tags.
 *
 * @see https://html.spec.whatwg.org/multipage/dom.html#interactive-content
 */
var interactiveContentTags = new Set(['a', 'audio', 'button', 'details', 'embed', 'iframe', 'input', 'label', 'select', 'textarea', 'video']);

function FormatEdit(_ref) {
  var formatTypes = _ref.formatTypes,
      onChange = _ref.onChange,
      onFocus = _ref.onFocus,
      value = _ref.value,
      allowedFormats = _ref.allowedFormats,
      withoutInteractiveFormatting = _ref.withoutInteractiveFormatting;
  return formatTypes.map(function (_ref2) {
    var name = _ref2.name,
        Edit = _ref2.edit,
        tagName = _ref2.tagName;

    if (!Edit) {
      return null;
    }

    if (allowedFormats && allowedFormats.indexOf(name) === -1) {
      return null;
    }

    if (withoutInteractiveFormatting && interactiveContentTags.has(tagName)) {
      return null;
    }

    var activeFormat = (0, _getActiveFormat.getActiveFormat)(value, name);
    var isActive = activeFormat !== undefined;
    var activeObject = (0, _getActiveObject.getActiveObject)(value);
    var isObjectActive = activeObject !== undefined && activeObject.type === name;
    return (0, _element.createElement)(Edit, {
      key: name,
      isActive: isActive,
      activeAttributes: isActive ? activeFormat.attributes || {} : {},
      isObjectActive: isObjectActive,
      activeObjectAttributes: isObjectActive ? activeObject.attributes || {} : {},
      value: value,
      onChange: onChange,
      onFocus: onFocus
    });
  });
}
//# sourceMappingURL=format-edit.js.map