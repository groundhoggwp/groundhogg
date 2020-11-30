import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { isFinite, omit } from 'lodash';
/**
 * WordPress dependencies
 */

import { RawHTML } from '@wordpress/element';
import { getColorClassName, getFontSizeClass, RichText } from '@wordpress/block-editor';
var supports = {
  className: false
};
var blockAttributes = {
  align: {
    type: 'string'
  },
  content: {
    type: 'string',
    source: 'html',
    selector: 'p',
    default: ''
  },
  dropCap: {
    type: 'boolean',
    default: false
  },
  placeholder: {
    type: 'string'
  },
  textColor: {
    type: 'string'
  },
  backgroundColor: {
    type: 'string'
  },
  fontSize: {
    type: 'string'
  },
  direction: {
    type: 'string',
    enum: ['ltr', 'rtl']
  },
  style: {
    type: 'object'
  }
};

var migrateCustomColorsAndFontSizes = function migrateCustomColorsAndFontSizes(attributes) {
  if (!attributes.customTextColor && !attributes.customBackgroundColor && !attributes.customFontSize) {
    return attributes;
  }

  var style = {};

  if (attributes.customTextColor || attributes.customBackgroundColor) {
    style.color = {};
  }

  if (attributes.customTextColor) {
    style.color.text = attributes.customTextColor;
  }

  if (attributes.customBackgroundColor) {
    style.color.background = attributes.customBackgroundColor;
  }

  if (attributes.customFontSize) {
    style.typography = {
      fontSize: attributes.customFontSize
    };
  }

  return _objectSpread(_objectSpread({}, omit(attributes, ['customTextColor', 'customBackgroundColor', 'customFontSize'])), {}, {
    style: style
  });
};

var deprecated = [{
  supports: supports,
  attributes: _objectSpread(_objectSpread({}, omit(blockAttributes, ['style'])), {}, {
    customTextColor: {
      type: 'string'
    },
    customBackgroundColor: {
      type: 'string'
    },
    customFontSize: {
      type: 'number'
    }
  }),
  migrate: migrateCustomColorsAndFontSizes,
  save: function save(_ref) {
    var _classnames;

    var attributes = _ref.attributes;
    var align = attributes.align,
        content = attributes.content,
        dropCap = attributes.dropCap,
        backgroundColor = attributes.backgroundColor,
        textColor = attributes.textColor,
        customBackgroundColor = attributes.customBackgroundColor,
        customTextColor = attributes.customTextColor,
        fontSize = attributes.fontSize,
        customFontSize = attributes.customFontSize,
        direction = attributes.direction;
    var textClass = getColorClassName('color', textColor);
    var backgroundClass = getColorClassName('background-color', backgroundColor);
    var fontSizeClass = getFontSizeClass(fontSize);
    var className = classnames((_classnames = {
      'has-text-color': textColor || customTextColor,
      'has-background': backgroundColor || customBackgroundColor,
      'has-drop-cap': dropCap
    }, _defineProperty(_classnames, "has-text-align-".concat(align), align), _defineProperty(_classnames, fontSizeClass, fontSizeClass), _defineProperty(_classnames, textClass, textClass), _defineProperty(_classnames, backgroundClass, backgroundClass), _classnames));
    var styles = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      color: textClass ? undefined : customTextColor,
      fontSize: fontSizeClass ? undefined : customFontSize
    };
    return createElement(RichText.Content, {
      tagName: "p",
      style: styles,
      className: className ? className : undefined,
      value: content,
      dir: direction
    });
  }
}, {
  supports: supports,
  attributes: _objectSpread(_objectSpread({}, omit(blockAttributes, ['style'])), {}, {
    customTextColor: {
      type: 'string'
    },
    customBackgroundColor: {
      type: 'string'
    },
    customFontSize: {
      type: 'number'
    }
  }),
  migrate: migrateCustomColorsAndFontSizes,
  save: function save(_ref2) {
    var _classnames2;

    var attributes = _ref2.attributes;
    var align = attributes.align,
        content = attributes.content,
        dropCap = attributes.dropCap,
        backgroundColor = attributes.backgroundColor,
        textColor = attributes.textColor,
        customBackgroundColor = attributes.customBackgroundColor,
        customTextColor = attributes.customTextColor,
        fontSize = attributes.fontSize,
        customFontSize = attributes.customFontSize,
        direction = attributes.direction;
    var textClass = getColorClassName('color', textColor);
    var backgroundClass = getColorClassName('background-color', backgroundColor);
    var fontSizeClass = getFontSizeClass(fontSize);
    var className = classnames((_classnames2 = {
      'has-text-color': textColor || customTextColor,
      'has-background': backgroundColor || customBackgroundColor,
      'has-drop-cap': dropCap
    }, _defineProperty(_classnames2, fontSizeClass, fontSizeClass), _defineProperty(_classnames2, textClass, textClass), _defineProperty(_classnames2, backgroundClass, backgroundClass), _classnames2));
    var styles = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      color: textClass ? undefined : customTextColor,
      fontSize: fontSizeClass ? undefined : customFontSize,
      textAlign: align
    };
    return createElement(RichText.Content, {
      tagName: "p",
      style: styles,
      className: className ? className : undefined,
      value: content,
      dir: direction
    });
  }
}, {
  supports: supports,
  attributes: _objectSpread(_objectSpread({}, omit(blockAttributes, ['style'])), {}, {
    customTextColor: {
      type: 'string'
    },
    customBackgroundColor: {
      type: 'string'
    },
    customFontSize: {
      type: 'number'
    },
    width: {
      type: 'string'
    }
  }),
  migrate: migrateCustomColorsAndFontSizes,
  save: function save(_ref3) {
    var _classnames3;

    var attributes = _ref3.attributes;
    var width = attributes.width,
        align = attributes.align,
        content = attributes.content,
        dropCap = attributes.dropCap,
        backgroundColor = attributes.backgroundColor,
        textColor = attributes.textColor,
        customBackgroundColor = attributes.customBackgroundColor,
        customTextColor = attributes.customTextColor,
        fontSize = attributes.fontSize,
        customFontSize = attributes.customFontSize;
    var textClass = getColorClassName('color', textColor);
    var backgroundClass = getColorClassName('background-color', backgroundColor);
    var fontSizeClass = fontSize && "is-".concat(fontSize, "-text");
    var className = classnames((_classnames3 = {}, _defineProperty(_classnames3, "align".concat(width), width), _defineProperty(_classnames3, 'has-background', backgroundColor || customBackgroundColor), _defineProperty(_classnames3, 'has-drop-cap', dropCap), _defineProperty(_classnames3, fontSizeClass, fontSizeClass), _defineProperty(_classnames3, textClass, textClass), _defineProperty(_classnames3, backgroundClass, backgroundClass), _classnames3));
    var styles = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      color: textClass ? undefined : customTextColor,
      fontSize: fontSizeClass ? undefined : customFontSize,
      textAlign: align
    };
    return createElement(RichText.Content, {
      tagName: "p",
      style: styles,
      className: className ? className : undefined,
      value: content
    });
  }
}, {
  supports: supports,
  attributes: omit(_objectSpread(_objectSpread({}, blockAttributes), {}, {
    fontSize: {
      type: 'number'
    }
  }), ['style']),
  save: function save(_ref4) {
    var _classnames4;

    var attributes = _ref4.attributes;
    var width = attributes.width,
        align = attributes.align,
        content = attributes.content,
        dropCap = attributes.dropCap,
        backgroundColor = attributes.backgroundColor,
        textColor = attributes.textColor,
        fontSize = attributes.fontSize;
    var className = classnames((_classnames4 = {}, _defineProperty(_classnames4, "align".concat(width), width), _defineProperty(_classnames4, 'has-background', backgroundColor), _defineProperty(_classnames4, 'has-drop-cap', dropCap), _classnames4));
    var styles = {
      backgroundColor: backgroundColor,
      color: textColor,
      fontSize: fontSize,
      textAlign: align
    };
    return createElement("p", {
      style: styles,
      className: className ? className : undefined
    }, content);
  },
  migrate: function migrate(attributes) {
    return migrateCustomColorsAndFontSizes(omit(_objectSpread(_objectSpread({}, attributes), {}, {
      customFontSize: isFinite(attributes.fontSize) ? attributes.fontSize : undefined,
      customTextColor: attributes.textColor && '#' === attributes.textColor[0] ? attributes.textColor : undefined,
      customBackgroundColor: attributes.backgroundColor && '#' === attributes.backgroundColor[0] ? attributes.backgroundColor : undefined
    })), ['fontSize', 'textColor', 'backgroundColor', 'style']);
  }
}, {
  supports: supports,
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    content: {
      type: 'string',
      source: 'html',
      default: ''
    }
  }),
  save: function save(_ref5) {
    var attributes = _ref5.attributes;
    return createElement(RawHTML, null, attributes.content);
  },
  migrate: function migrate(attributes) {
    return attributes;
  }
}];
export default deprecated;
//# sourceMappingURL=deprecated.js.map