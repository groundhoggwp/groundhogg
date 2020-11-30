import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { difference, compact } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { hasBlockSupport, getSaveContent, parseWithAttributeSchema } from '@wordpress/blocks';
/**
 * Filters registered block settings, extending attributes with anchor using ID
 * of the first node.
 *
 * @param {Object} settings Original block settings.
 *
 * @return {Object} Filtered block settings.
 */

export function addAttribute(settings) {
  if (hasBlockSupport(settings, 'customClassName', true)) {
    // Gracefully handle if settings.attributes is undefined.
    settings.attributes = _objectSpread(_objectSpread({}, settings.attributes), {}, {
      className: {
        type: 'string'
      }
    });
  }

  return settings;
}
/**
 * Override props assigned to save component to inject anchor ID, if block
 * supports anchor. This is only applied if the block's save result is an
 * element and not a markup string.
 *
 * @param {Object} extraProps Additional props applied to save element.
 * @param {Object} blockType  Block type.
 * @param {Object} attributes Current block attributes.
 *
 * @return {Object} Filtered props applied to save element.
 */

export function addSaveProps(extraProps, blockType, attributes) {
  if (hasBlockSupport(blockType, 'customClassName', true) && attributes.className) {
    extraProps.className = classnames(extraProps.className, attributes.className);
  }

  return extraProps;
}
/**
 * Given an HTML string, returns an array of class names assigned to the root
 * element in the markup.
 *
 * @param {string} innerHTML Markup string from which to extract classes.
 *
 * @return {string[]} Array of class names assigned to the root element.
 */

export function getHTMLRootElementClasses(innerHTML) {
  innerHTML = "<div data-custom-class-name>".concat(innerHTML, "</div>");
  var parsed = parseWithAttributeSchema(innerHTML, {
    type: 'string',
    source: 'attribute',
    selector: '[data-custom-class-name] > *',
    attribute: 'class'
  });
  return parsed ? parsed.trim().split(/\s+/) : [];
}
/**
 * Given a parsed set of block attributes, if the block supports custom class
 * names and an unknown class (per the block's serialization behavior) is
 * found, the unknown classes are treated as custom classes. This prevents the
 * block from being considered as invalid.
 *
 * @param {Object} blockAttributes Original block attributes.
 * @param {Object} blockType       Block type settings.
 * @param {string} innerHTML       Original block markup.
 *
 * @return {Object} Filtered block attributes.
 */

export function addParsedDifference(blockAttributes, blockType, innerHTML) {
  if (hasBlockSupport(blockType, 'customClassName', true)) {
    // To determine difference, serialize block given the known set of
    // attributes. If there are classes which are mismatched with the
    // incoming HTML of the block, add to filtered result.
    var serialized = getSaveContent(blockType, blockAttributes);
    var classes = getHTMLRootElementClasses(serialized);
    var parsedClasses = getHTMLRootElementClasses(innerHTML);
    var customClasses = difference(parsedClasses, classes);
    var filteredClassName = compact([blockAttributes.className].concat(_toConsumableArray(customClasses))).join(' ');

    if (filteredClassName) {
      blockAttributes.className = filteredClassName;
    } else {
      delete blockAttributes.className;
    }
  }

  return blockAttributes;
}
addFilter('blocks.registerBlockType', 'core/custom-class-name/attribute', addAttribute);
addFilter('blocks.getSaveContent.extraProps', 'core/custom-class-name/save-props', addSaveProps);
addFilter('blocks.getBlockAttributes', 'core/custom-class-name/addParsedDifference', addParsedDifference);
//# sourceMappingURL=custom-class-name.native.js.map