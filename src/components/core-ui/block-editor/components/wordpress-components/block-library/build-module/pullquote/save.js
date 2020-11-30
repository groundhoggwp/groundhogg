import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { includes } from 'lodash';
/**
 * WordPress dependencies
 */

import { getColorClassName, RichText } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import { SOLID_COLOR_CLASS } from './shared';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var mainColor = attributes.mainColor,
      customMainColor = attributes.customMainColor,
      textColor = attributes.textColor,
      customTextColor = attributes.customTextColor,
      value = attributes.value,
      citation = attributes.citation,
      className = attributes.className;
  var isSolidColorStyle = includes(className, SOLID_COLOR_CLASS);
  var figureClasses, figureStyles; // Is solid color style

  if (isSolidColorStyle) {
    var backgroundClass = getColorClassName('background-color', mainColor);
    figureClasses = classnames(_defineProperty({
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

  var blockquoteTextColorClass = getColorClassName('color', textColor);
  var blockquoteClasses = (textColor || customTextColor) && classnames('has-text-color', _defineProperty({}, blockquoteTextColorClass, blockquoteTextColorClass));
  var blockquoteStyles = blockquoteTextColorClass ? undefined : {
    color: customTextColor
  };
  return createElement("figure", {
    className: figureClasses,
    style: figureStyles
  }, createElement("blockquote", {
    className: blockquoteClasses,
    style: blockquoteStyles
  }, createElement(RichText.Content, {
    value: value,
    multiline: true
  }), !RichText.isEmpty(citation) && createElement(RichText.Content, {
    tagName: "cite",
    value: citation
  })));
}
//# sourceMappingURL=save.js.map