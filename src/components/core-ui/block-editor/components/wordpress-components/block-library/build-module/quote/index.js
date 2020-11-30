import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { quote as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import deprecated from './deprecated';
import edit from './edit';
var metadata = {
  name: "core/quote",
  category: "text",
  attributes: {
    value: {
      type: "string",
      source: "html",
      selector: "blockquote",
      multiline: "p",
      "default": ""
    },
    citation: {
      type: "string",
      source: "html",
      selector: "cite",
      "default": ""
    },
    align: {
      type: "string"
    }
  },
  supports: {
    anchor: true,
    lightBlockWrapper: true
  }
};
import save from './save';
import transforms from './transforms';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Quote'),
  description: __('Give quoted text visual emphasis. "In quoting others, we cite ourselves." — Julio Cortázar'),
  icon: icon,
  keywords: [__('blockquote'), __('cite')],
  example: {
    attributes: {
      value: '<p>' + __('In quoting others, we cite ourselves.') + '</p>',
      citation: 'Julio Cortázar',
      className: 'is-style-large'
    }
  },
  styles: [{
    name: 'default',
    label: _x('Default', 'block style'),
    isDefault: true
  }, {
    name: 'large',
    label: _x('Large', 'block style')
  }],
  transforms: transforms,
  edit: edit,
  save: save,
  merge: function merge(attributes, _ref) {
    var value = _ref.value,
        citation = _ref.citation;

    // Quote citations cannot be merged. Pick the second one unless it's
    // empty.
    if (!citation) {
      citation = attributes.citation;
    }

    if (!value || value === '<p></p>') {
      return _objectSpread(_objectSpread({}, attributes), {}, {
        citation: citation
      });
    }

    return _objectSpread(_objectSpread({}, attributes), {}, {
      value: attributes.value + value,
      citation: citation
    });
  },
  deprecated: deprecated
};
//# sourceMappingURL=index.js.map