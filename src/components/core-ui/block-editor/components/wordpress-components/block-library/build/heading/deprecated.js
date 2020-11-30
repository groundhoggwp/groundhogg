"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames4 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _blockEditor = require("@wordpress/block-editor");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var blockSupports = {
  className: false,
  anchor: true
};
var blockAttributes = {
  align: {
    type: 'string'
  },
  content: {
    type: 'string',
    source: 'html',
    selector: 'h1,h2,h3,h4,h5,h6',
    default: ''
  },
  level: {
    type: 'number',
    default: 2
  },
  placeholder: {
    type: 'string'
  }
};

var migrateCustomColors = function migrateCustomColors(attributes) {
  if (!attributes.customTextColor) {
    return attributes;
  }

  var style = {
    color: {
      text: attributes.customTextColor
    }
  };
  return _objectSpread(_objectSpread({}, (0, _lodash.omit)(attributes, ['customTextColor'])), {}, {
    style: style
  });
};

var deprecated = [{
  supports: blockSupports,
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    customTextColor: {
      type: 'string'
    },
    textColor: {
      type: 'string'
    }
  }),
  migrate: migrateCustomColors,
  save: function save(_ref) {
    var _classnames;

    var attributes = _ref.attributes;
    var align = attributes.align,
        content = attributes.content,
        customTextColor = attributes.customTextColor,
        level = attributes.level,
        textColor = attributes.textColor;
    var tagName = 'h' + level;
    var textClass = (0, _blockEditor.getColorClassName)('color', textColor);
    var className = (0, _classnames4.default)((_classnames = {}, (0, _defineProperty2.default)(_classnames, textClass, textClass), (0, _defineProperty2.default)(_classnames, 'has-text-color', textColor || customTextColor), (0, _defineProperty2.default)(_classnames, "has-text-align-".concat(align), align), _classnames));
    return (0, _element.createElement)(_blockEditor.RichText.Content, {
      className: className ? className : undefined,
      tagName: tagName,
      style: {
        color: textClass ? undefined : customTextColor
      },
      value: content
    });
  }
}, {
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    customTextColor: {
      type: 'string'
    },
    textColor: {
      type: 'string'
    }
  }),
  migrate: migrateCustomColors,
  save: function save(_ref2) {
    var _classnames2;

    var attributes = _ref2.attributes;
    var align = attributes.align,
        content = attributes.content,
        customTextColor = attributes.customTextColor,
        level = attributes.level,
        textColor = attributes.textColor;
    var tagName = 'h' + level;
    var textClass = (0, _blockEditor.getColorClassName)('color', textColor);
    var className = (0, _classnames4.default)((_classnames2 = {}, (0, _defineProperty2.default)(_classnames2, textClass, textClass), (0, _defineProperty2.default)(_classnames2, "has-text-align-".concat(align), align), _classnames2));
    return (0, _element.createElement)(_blockEditor.RichText.Content, {
      className: className ? className : undefined,
      tagName: tagName,
      style: {
        color: textClass ? undefined : customTextColor
      },
      value: content
    });
  },
  supports: blockSupports
}, {
  supports: blockSupports,
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    customTextColor: {
      type: 'string'
    },
    textColor: {
      type: 'string'
    }
  }),
  migrate: migrateCustomColors,
  save: function save(_ref3) {
    var attributes = _ref3.attributes;
    var align = attributes.align,
        level = attributes.level,
        content = attributes.content,
        textColor = attributes.textColor,
        customTextColor = attributes.customTextColor;
    var tagName = 'h' + level;
    var textClass = (0, _blockEditor.getColorClassName)('color', textColor);
    var className = (0, _classnames4.default)((0, _defineProperty2.default)({}, textClass, textClass));
    return (0, _element.createElement)(_blockEditor.RichText.Content, {
      className: className ? className : undefined,
      tagName: tagName,
      style: {
        textAlign: align,
        color: textClass ? undefined : customTextColor
      },
      value: content
    });
  }
}];
var _default = deprecated;
exports.default = _default;
//# sourceMappingURL=deprecated.js.map