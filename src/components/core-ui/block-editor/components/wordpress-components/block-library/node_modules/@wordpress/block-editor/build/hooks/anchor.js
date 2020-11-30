"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.addAttribute = addAttribute;
exports.addSaveProps = addSaveProps;
exports.withInspectorControl = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _hooks = require("@wordpress/hooks");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _compose = require("@wordpress/compose");

var _components2 = require("../components");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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

function addAttribute(settings) {
  // allow blocks to specify their own attribute definition with default values if needed.
  if ((0, _lodash.has)(settings.attributes, ['anchor', 'type'])) {
    return settings;
  }

  if ((0, _blocks.hasBlockSupport)(settings, 'anchor')) {
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


var withInspectorControl = (0, _compose.createHigherOrderComponent)(function (BlockEdit) {
  return function (props) {
    var hasAnchor = (0, _blocks.hasBlockSupport)(props.name, 'anchor');

    if (hasAnchor && props.isSelected) {
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(BlockEdit, props), (0, _element.createElement)(_components2.InspectorAdvancedControls, null, (0, _element.createElement)(_components.TextControl, {
        className: "html-anchor-control",
        label: (0, _i18n.__)('HTML anchor'),
        help: (0, _element.createElement)(_element.Fragment, null, (0, _i18n.__)('Enter a word or two — without spaces — to make a unique web address just for this heading, called an “anchor.” Then, you’ll be able to link directly to this section of your page.'), (0, _element.createElement)(_components.ExternalLink, {
          href: 'https://wordpress.org/support/article/page-jumps/'
        }, (0, _i18n.__)('Learn more about anchors'))),
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

    return (0, _element.createElement)(BlockEdit, props);
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

exports.withInspectorControl = withInspectorControl;

function addSaveProps(extraProps, blockType, attributes) {
  if ((0, _blocks.hasBlockSupport)(blockType, 'anchor')) {
    extraProps.id = attributes.anchor === '' ? null : attributes.anchor;
  }

  return extraProps;
}

(0, _hooks.addFilter)('blocks.registerBlockType', 'core/anchor/attribute', addAttribute);
(0, _hooks.addFilter)('editor.BlockEdit', 'core/editor/anchor/with-inspector-control', withInspectorControl);
(0, _hooks.addFilter)('blocks.getSaveContent.extraProps', 'core/anchor/save-props', addSaveProps);
//# sourceMappingURL=anchor.js.map