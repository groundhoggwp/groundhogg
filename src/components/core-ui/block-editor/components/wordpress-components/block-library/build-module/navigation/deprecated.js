import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { omit } from 'lodash';
/**
 * WordPress dependencies
 */

import { InnerBlocks } from '@wordpress/block-editor';
export default [{
  attributes: {
    className: {
      type: 'string'
    },
    textColor: {
      type: 'string'
    },
    rgbTextColor: {
      type: 'string'
    },
    backgroundColor: {
      type: 'string'
    },
    rgbBackgroundColor: {
      type: 'string'
    },
    fontSize: {
      type: 'string'
    },
    customFontSize: {
      type: 'number'
    },
    itemsJustification: {
      type: 'string'
    },
    showSubmenuIcon: {
      type: 'boolean'
    }
  },
  isEligible: function isEligible(attribute) {
    return attribute.rgbTextColor || attribute.rgbBackgroundColor;
  },
  supports: {
    align: ['wide', 'full'],
    anchor: true,
    html: false,
    inserter: true
  },
  migrate: function migrate(attributes) {
    return _objectSpread(_objectSpread({}, omit(attributes, ['rgbTextColor', 'rgbBackgroundColor'])), {}, {
      customTextColor: attributes.textColor ? undefined : attributes.rgbTextColor,
      customBackgroundColor: attributes.backgroundColor ? undefined : attributes.rgbBackgroundColor
    });
  },
  save: function save() {
    return createElement(InnerBlocks.Content, null);
  }
}];
//# sourceMappingURL=deprecated.js.map