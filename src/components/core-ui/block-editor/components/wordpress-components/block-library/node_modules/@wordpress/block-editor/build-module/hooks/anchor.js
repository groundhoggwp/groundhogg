import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { has } from 'lodash';
/**
 * WordPress dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { TextControl, ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { hasBlockSupport } from '@wordpress/blocks';
import { createHigherOrderComponent } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { InspectorAdvancedControls } from '../components';
/**
 * Regular expression matching invalid anchor characters for replacement.
 *
 * @type {RegExp}
 */

var ANCHOR_REGEX = /[\s#]/g;
/**
 * Filters registered block settings, extending attributes with anchor using ID
 * of the first node.
 *
 * @param {Object} settings Original block settings.
 *
 * @return {Object} Filtered block settings.
 */

export function addAttribute(settings) {
  // allow blocks to specify their own attribute definition with default values if needed.
  if (has(settings.attributes, ['anchor', 'type'])) {
    return settings;
  }

  if (hasBlockSupport(settings, 'anchor')) {
    // Gracefully handle if settings.attributes is undefined.
    settings.attributes = _objectSpread(_objectSpread({}, settings.attributes), {}, {
      anchor: {
        type: 'string',
        source: 'attribute',
        attribute: 'id',
        selector: '*'
      }
    });
  }

  return settings;
}
/**
 * Override the default edit UI to include a new block inspector control for
 * assigning the anchor ID, if block supports anchor.
 *
 * @param {WPComponent} BlockEdit Original component.
 *
 * @return {WPComponent} Wrapped component.
 */

export var withInspectorControl = createHigherOrderComponent(function (BlockEdit) {
  return function (props) {
    var hasAnchor = hasBlockSupport(props.name, 'anchor');

    if (hasAnchor && props.isSelected) {
      return createElement(Fragment, null, createElement(BlockEdit, props), createElement(InspectorAdvancedControls, null, createElement(TextControl, {
        className: "html-anchor-control",
        label: __('HTML anchor'),
        help: createElement(Fragment, null, __('Enter a word or two — without spaces — to make a unique web address just for this heading, called an “anchor.” Then, you’ll be able to link directly to this section of your page.'), createElement(ExternalLink, {
          href: 'https://wordpress.org/support/article/page-jumps/'
        }, __('Learn more about anchors'))),
        value: props.attributes.anchor || '',
        onChange: function onChange(nextValue) {
          nextValue = nextValue.replace(ANCHOR_REGEX, '-');
          props.setAttributes({
            anchor: nextValue
          });
        },
        autoComplete: "off"
      })));
    }

    return createElement(BlockEdit, props);
  };
}, 'withInspectorControl');
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
  if (hasBlockSupport(blockType, 'anchor')) {
    extraProps.id = attributes.anchor === '' ? null : attributes.anchor;
  }

  return extraProps;
}
addFilter('blocks.registerBlockType', 'core/anchor/attribute', addAttribute);
addFilter('editor.BlockEdit', 'core/editor/anchor/with-inspector-control', withInspectorControl);
addFilter('blocks.getSaveContent.extraProps', 'core/anchor/save-props', addSaveProps);
//# sourceMappingURL=anchor.js.map