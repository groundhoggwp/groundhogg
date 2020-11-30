"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames3 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _blockEditor = require("@wordpress/block-editor");

var _shared = require("./shared");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function save(_ref) {
  var attributes = _ref.attributes;
  var mainColor = attributes.mainColor,
      customMainColor = attributes.customMainColor,
      textColor = attributes.textColor,
      customTextColor = attributes.customTextColor,
      value = attributes.value,
      citation = attributes.citation,
      className = attributes.className;
  var isSolidColorStyle = (0, _lodash.includes)(className, _shared.SOLID_COLOR_CLASS);
  var figureClasses, figureStyles; // Is solid color style

  if (isSolidColorStyle) {
    var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', mainColor);
    figureClasses = (0, _classnames3.default)((0, _defineProperty2.default)({
      'has-background': backgroundClass || customMainColor
    }, backgroundClass, backgroundClass));
    figureStyles = {
      backgroundColor: backgroundClass ? undefined : customMainColor
    }; // Is normal style and a custom color is being used ( we can set a style directly with its value)
  } else if (customMainColor) {
    figureStyles = {
      borderColor: customMainColor
    };
  }

  var blockquoteTextColorClass = (0, _blockEditor.getColorClassName)('color', textColor);
  var blockquoteClasses = (textColor || customTextColor) && (0, _classnames3.default)('has-text-color', (0, _defineProperty2.default)({}, blockquoteTextColorClass, blockquoteTextColorClass));
  var blockquoteStyles = blockquoteTextColorClass ? undefined : {
    color: customTextColor
  };
  return (0, _element.createElement)("figure", {
    className: figureClasses,
    style: figureStyles
  }, (0, _element.createElement)("blockquote", {
    className: blockquoteClasses,
    style: blockquoteStyles
  }, (0, _element.createElement)(_blockEditor.RichText.Content, {
    value: value,
    multiline: true
  }), !_blockEditor.RichText.isEmpty(citation) && (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "cite",
    value: citation
  })));
}
//# sourceMappingURL=save.js.map