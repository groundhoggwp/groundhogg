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

var blockAttributes = {
  value: {
    type: 'string',
    source: 'html',
    selector: 'blockquote',
    multiline: 'p',
    default: ''
  },
  citation: {
    type: 'string',
    source: 'html',
    selector: 'cite',
    default: ''
  },
  align: {
    type: 'string'
  }
};
var deprecated = [{
  attributes: blockAttributes,
  save: function save(_ref) {
    var attributes = _ref.attributes;
    var align = attributes.align,
        value = attributes.value,
        citation = attributes.citation;
    return (0, _element.createElement)("blockquote", {
      style: {
        textAlign: align ? align : null
      }
    }, (0, _element.createElement)(_blockEditor.RichText.Content, {
      multiline: true,
      value: value
    }), !_blockEditor.RichText.isEmpty(citation) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "cite",
      value: citation
    }));
  }
}, {
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    style: {
      type: 'number',
      default: 1
    }
  }),
  migrate: function migrate(attributes) {
    if (attributes.style === 2) {
      return _objectSpread(_objectSpread({}, (0, _lodash.omit)(attributes, ['style'])), {}, {
        className: attributes.className ? attributes.className + ' is-style-large' : 'is-style-large'
      });
    }

    return attributes;
  },
  save: function save(_ref2) {
    var attributes = _ref2.attributes;
    var align = attributes.align,
        value = attributes.value,
        citation = attributes.citation,
        style = attributes.style;
    return (0, _element.createElement)("blockquote", {
      className: style === 2 ? 'is-large' : '',
      style: {
        textAlign: align ? align : null
      }
    }, (0, _element.createElement)(_blockEditor.RichText.Content, {
      multiline: true,
      value: value
    }), !_blockEditor.RichText.isEmpty(citation) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "cite",
      value: citation
    }));
  }
}, {
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    citation: {
      type: 'string',
      source: 'html',
      selector: 'footer',
      default: ''
    },
    style: {
      type: 'number',
      default: 1
    }
  }),
  save: function save(_ref3) {
    var attributes = _ref3.attributes;
    var align = attributes.align,
        value = attributes.value,
        citation = attributes.citation,
        style = attributes.style;
    return (0, _element.createElement)("blockquote", {
      className: "blocks-quote-style-".concat(style),
      style: {
        textAlign: align ? align : null
      }
    }, (0, _element.createElement)(_blockEditor.RichText.Content, {
      multiline: true,
      value: value
    }), !_blockEditor.RichText.isEmpty(citation) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "footer",
      value: citation
    }));
  }
}];
var _default = deprecated;
exports.default = _default;
//# sourceMappingURL=deprecated.js.map