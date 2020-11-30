import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { omit } from 'lodash';
/**
 * WordPress dependencies
 */

import { getColorClassName, RichText } from '@wordpress/block-editor';
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
  return _objectSpread(_objectSpread({}, omit(attributes, ['customTextColor'])), {}, {
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
    var textClass = getColorClassName('color', textColor);
    var className = classnames((_classnames = {}, _defineProperty(_classnames, textClass, textClass), _defineProperty(_classnames, 'has-text-color', textColor || customTextColor), _defineProperty(_classnames, "has-text-align-".concat(align), align), _classnames));
    return createElement(RichText.Content, {
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
    var textClass = getColorClassName('color', textColor);
    var className = classnames((_classnames2 = {}, _defineProperty(_classnames2, textClass, textClass), _defineProperty(_classnames2, "has-text-align-".concat(align), align), _classnames2));
    return createElement(RichText.Content, {
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
    var textClass = getColorClassName('color', textColor);
    var className = classnames(_defineProperty({}, textClass, textClass));
    return createElement(RichText.Content, {
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
export default deprecated;
//# sourceMappingURL=deprecated.js.map