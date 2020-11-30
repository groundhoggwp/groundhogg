"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _blockEditor = require("@wordpress/block-editor");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var migrateAttributes = function migrateAttributes(attributes) {
  if (!attributes.tagName) {
    attributes = _objectSpread(_objectSpread({}, attributes), {}, {
      tagName: 'div'
    });
  }

  if (!attributes.customTextColor && !attributes.customBackgroundColor) {
    return attributes;
  }

  var style = {
    color: {}
  };

  if (attributes.customTextColor) {
    style.color.text = attributes.customTextColor;
  }

  if (attributes.customBackgroundColor) {
    style.color.background = attributes.customBackgroundColor;
  }

  return _objectSpread(_objectSpread({}, (0, _lodash.omit)(attributes, ['customTextColor', 'customBackgroundColor'])), {}, {
    style: style
  });
};

var deprecated = [// Version of the block without global styles support
{
  attributes: {
    backgroundColor: {
      type: 'string'
    },
    customBackgroundColor: {
      type: 'string'
    },
    textColor: {
      type: 'string'
    },
    customTextColor: {
      type: 'string'
    }
  },
  supports: {
    align: ['wide', 'full'],
    anchor: true,
    html: false
  },
  migrate: migrateAttributes,
  save: function save(_ref) {
    var attributes = _ref.attributes;
    var backgroundColor = attributes.backgroundColor,
        customBackgroundColor = attributes.customBackgroundColor,
        textColor = attributes.textColor,
        customTextColor = attributes.customTextColor;
    var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', backgroundColor);
    var textClass = (0, _blockEditor.getColorClassName)('color', textColor);
    var className = (0, _classnames.default)(backgroundClass, textClass, {
      'has-text-color': textColor || customTextColor,
      'has-background': backgroundColor || customBackgroundColor
    });
    var styles = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      color: textClass ? undefined : customTextColor
    };
    return (0, _element.createElement)("div", {
      className: className,
      style: styles
    }, (0, _element.createElement)("div", {
      className: "wp-block-group__inner-container"
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null)));
  }
}, // Version of the group block with a bug that made text color class not applied.
{
  attributes: {
    backgroundColor: {
      type: 'string'
    },
    customBackgroundColor: {
      type: 'string'
    },
    textColor: {
      type: 'string'
    },
    customTextColor: {
      type: 'string'
    }
  },
  migrate: migrateAttributes,
  supports: {
    align: ['wide', 'full'],
    anchor: true,
    html: false
  },
  save: function save(_ref2) {
    var attributes = _ref2.attributes;
    var backgroundColor = attributes.backgroundColor,
        customBackgroundColor = attributes.customBackgroundColor,
        textColor = attributes.textColor,
        customTextColor = attributes.customTextColor;
    var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', backgroundColor);
    var textClass = (0, _blockEditor.getColorClassName)('color', textColor);
    var className = (0, _classnames.default)(backgroundClass, {
      'has-text-color': textColor || customTextColor,
      'has-background': backgroundColor || customBackgroundColor
    });
    var styles = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      color: textClass ? undefined : customTextColor
    };
    return (0, _element.createElement)("div", {
      className: className,
      style: styles
    }, (0, _element.createElement)("div", {
      className: "wp-block-group__inner-container"
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null)));
  }
}, // v1 of group block. Deprecated to add an inner-container div around `InnerBlocks.Content`.
{
  attributes: {
    backgroundColor: {
      type: 'string'
    },
    customBackgroundColor: {
      type: 'string'
    }
  },
  supports: {
    align: ['wide', 'full'],
    anchor: true,
    html: false
  },
  migrate: migrateAttributes,
  save: function save(_ref3) {
    var attributes = _ref3.attributes;
    var backgroundColor = attributes.backgroundColor,
        customBackgroundColor = attributes.customBackgroundColor;
    var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', backgroundColor);
    var className = (0, _classnames.default)(backgroundClass, {
      'has-background': backgroundColor || customBackgroundColor
    });
    var styles = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor
    };
    return (0, _element.createElement)("div", {
      className: className,
      style: styles
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null));
  }
}];
var _default = deprecated;
exports.default = _default;
//# sourceMappingURL=deprecated.js.map