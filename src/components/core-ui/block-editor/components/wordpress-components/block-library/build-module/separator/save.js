import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { getColorClassName } from '@wordpress/block-editor';
export default function separatorSave(_ref) {
  var _classnames;

  var attributes = _ref.attributes;
  var color = attributes.color,
      customColor = attributes.customColor; // the hr support changing color using border-color, since border-color
  // is not yet supported in the color palette, we use background-color

  var backgroundClass = getColorClassName('background-color', color); // the dots styles uses text for the dots, to change those dots color is
  // using color, not backgroundColor

  var colorClass = getColorClassName('color', color);
  var separatorClasses = classnames((_classnames = {
    'has-text-color has-background': color || customColor
  }, _defineProperty(_classnames, backgroundClass, backgroundClass), _defineProperty(_classnames, colorClass, colorClass), _classnames));
  var separatorStyle = {
    backgroundColor: backgroundClass ? undefined : customColor,
    color: colorClass ? undefined : customColor
  };
  return createElement("hr", {
    className: separatorClasses,
    style: separatorStyle
  });
}
//# sourceMappingURL=save.js.map