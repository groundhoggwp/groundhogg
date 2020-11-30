import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { RichText } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import getColorAndStyleProps from './color-props';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var borderRadius = attributes.borderRadius,
      linkTarget = attributes.linkTarget,
      rel = attributes.rel,
      text = attributes.text,
      title = attributes.title,
      url = attributes.url;
  var colorProps = getColorAndStyleProps(attributes);
  var buttonClasses = classnames('wp-block-button__link', colorProps.className, {
    'no-border-radius': borderRadius === 0
  });

  var buttonStyle = _objectSpread({
    borderRadius: borderRadius ? borderRadius + 'px' : undefined
  }, colorProps.style); // The use of a `title` attribute here is soft-deprecated, but still applied
  // if it had already been assigned, for the sake of backward-compatibility.
  // A title will no longer be assigned for new or updated button block links.


  return createElement("div", null, createElement(RichText.Content, {
    tagName: "a",
    className: buttonClasses,
    href: url,
    title: title,
    style: buttonStyle,
    value: text,
    target: linkTarget,
    rel: rel
  }));
}
//# sourceMappingURL=save.js.map