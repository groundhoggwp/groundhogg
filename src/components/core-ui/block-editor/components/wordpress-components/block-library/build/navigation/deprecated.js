"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _blockEditor = require("@wordpress/block-editor");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var _default = [{
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
    return _objectSpread(_objectSpread({}, (0, _lodash.omit)(attributes, ['rgbTextColor', 'rgbBackgroundColor'])), {}, {
      customTextColor: attributes.textColor ? undefined : attributes.rgbTextColor,
      customBackgroundColor: attributes.backgroundColor ? undefined : attributes.rgbBackgroundColor
    });
  },
  save: function save() {
    return (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null);
  }
}];
exports.default = _default;
//# sourceMappingURL=deprecated.js.map