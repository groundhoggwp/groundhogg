import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { get, includes } from 'lodash';
/**
 * WordPress dependencies
 */

import { getColorClassName, getColorObjectByAttributeValues, RichText } from '@wordpress/block-editor';
import { select } from '@wordpress/data';
/**
 * Internal dependencies
 */

import { SOLID_COLOR_CLASS } from './shared';
var blockAttributes = {
  value: {
    type: 'string',
    source: 'html',
    selector: 'blockquote',
    multiline: 'p'
  },
  citation: {
    type: 'string',
    source: 'html',
    selector: 'cite',
    default: ''
  },
  mainColor: {
    type: 'string'
  },
  customMainColor: {
    type: 'string'
  },
  textColor: {
    type: 'string'
  },
  customTextColor: {
    type: 'string'
  }
};

function parseBorderColor(styleString) {
  if (!styleString) {
    return;
  }

  var matches = styleString.match(/border-color:([^;]+)[;]?/);

  if (matches && matches[1]) {
    return matches[1];
  }
}

var deprecated = [{
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    // figureStyle is an attribute that never existed.
    // We are using it as a way to access the styles previously applied to the figure.
    figureStyle: {
      source: 'attribute',
      selector: 'figure',
      attribute: 'style'
    }
  }),
  save: function save(_ref) {
    var attributes = _ref.attributes;
    var mainColor = attributes.mainColor,
        customMainColor = attributes.customMainColor,
        textColor = attributes.textColor,
        customTextColor = attributes.customTextColor,
        value = attributes.value,
        citation = attributes.citation,
        className = attributes.className,
        figureStyle = attributes.figureStyle;
    var isSolidColorStyle = includes(className, SOLID_COLOR_CLASS);
    var figureClasses, figureStyles; // Is solid color style

    if (isSolidColorStyle) {
      var backgroundClass = getColorClassName('background-color', mainColor);
      figureClasses = classnames(_defineProperty({
        'has-background': backgroundClass || customMainColor
      }, backgroundClass, backgroundClass));
      figureStyles = {
        backgroundColor: backgroundClass ? undefined : customMainColor
      }; // Is normal style and a custom color is being used ( we can set a style directly with its value)
    } else if (customMainColor) {
      figureStyles = {
        borderColor: customMainColor
      }; // If normal style and a named color are being used, we need to retrieve the color value to set the style,
      // as there is no expectation that themes create classes that set border colors.
    } else if (mainColor) {
      // Previously here we queried the color settings to know the color value
      // of a named color. This made the save function impure and the block was refactored,
      // because meanwhile a change in the editor made it impossible to query color settings in the save function.
      // Here instead of querying the color settings to know the color value, we retrieve the value
      // directly from the style previously serialized.
      var borderColor = parseBorderColor(figureStyle);
      figureStyles = {
        borderColor: borderColor
      };
    }

    var blockquoteTextColorClass = getColorClassName('color', textColor);
    var blockquoteClasses = (textColor || customTextColor) && classnames('has-text-color', _defineProperty({}, blockquoteTextColorClass, blockquoteTextColorClass));
    var blockquoteStyles = blockquoteTextColorClass ? undefined : {
      color: customTextColor
    };
    return createElement("figure", {
      className: figureClasses,
      style: figureStyles
    }, createElement("blockquote", {
      className: blockquoteClasses,
      style: blockquoteStyles
    }, createElement(RichText.Content, {
      value: value,
      multiline: true
    }), !RichText.isEmpty(citation) && createElement(RichText.Content, {
      tagName: "cite",
      value: citation
    })));
  },
  migrate: function migrate(_ref2) {
    var className = _ref2.className,
        figureStyle = _ref2.figureStyle,
        mainColor = _ref2.mainColor,
        attributes = _objectWithoutProperties(_ref2, ["className", "figureStyle", "mainColor"]);

    var isSolidColorStyle = includes(className, SOLID_COLOR_CLASS); // If is the default style, and a main color is set,
    // migrate the main color value into a custom color.
    // The custom color value is retrived by parsing the figure styles.

    if (!isSolidColorStyle && mainColor && figureStyle) {
      var borderColor = parseBorderColor(figureStyle);

      if (borderColor) {
        return _objectSpread(_objectSpread({}, attributes), {}, {
          className: className,
          customMainColor: borderColor
        });
      }
    }

    return _objectSpread({
      className: className,
      mainColor: mainColor
    }, attributes);
  }
}, {
  attributes: blockAttributes,
  save: function save(_ref3) {
    var attributes = _ref3.attributes;
    var mainColor = attributes.mainColor,
        customMainColor = attributes.customMainColor,
        textColor = attributes.textColor,
        customTextColor = attributes.customTextColor,
        value = attributes.value,
        citation = attributes.citation,
        className = attributes.className;
    var isSolidColorStyle = includes(className, SOLID_COLOR_CLASS);
    var figureClass, figureStyles; // Is solid color style

    if (isSolidColorStyle) {
      figureClass = getColorClassName('background-color', mainColor);

      if (!figureClass) {
        figureStyles = {
          backgroundColor: customMainColor
        };
      } // Is normal style and a custom color is being used ( we can set a style directly with its value)

    } else if (customMainColor) {
      figureStyles = {
        borderColor: customMainColor
      }; // Is normal style and a named color is being used, we need to retrieve the color value to set the style,
      // as there is no expectation that themes create classes that set border colors.
    } else if (mainColor) {
      var colors = get(select('core/block-editor').getSettings(), ['colors'], []);
      var colorObject = getColorObjectByAttributeValues(colors, mainColor);
      figureStyles = {
        borderColor: colorObject.color
      };
    }

    var blockquoteTextColorClass = getColorClassName('color', textColor);
    var blockquoteClasses = textColor || customTextColor ? classnames('has-text-color', _defineProperty({}, blockquoteTextColorClass, blockquoteTextColorClass)) : undefined;
    var blockquoteStyle = blockquoteTextColorClass ? undefined : {
      color: customTextColor
    };
    return createElement("figure", {
      className: figureClass,
      style: figureStyles
    }, createElement("blockquote", {
      className: blockquoteClasses,
      style: blockquoteStyle
    }, createElement(RichText.Content, {
      value: value,
      multiline: true
    }), !RichText.isEmpty(citation) && createElement(RichText.Content, {
      tagName: "cite",
      value: citation
    })));
  }
}, {
  attributes: _objectSpread({}, blockAttributes),
  save: function save(_ref4) {
    var attributes = _ref4.attributes;
    var value = attributes.value,
        citation = attributes.citation;
    return createElement("blockquote", null, createElement(RichText.Content, {
      value: value,
      multiline: true
    }), !RichText.isEmpty(citation) && createElement(RichText.Content, {
      tagName: "cite",
      value: citation
    }));
  }
}, {
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    citation: {
      type: 'string',
      source: 'html',
      selector: 'footer'
    },
    align: {
      type: 'string',
      default: 'none'
    }
  }),
  save: function save(_ref5) {
    var attributes = _ref5.attributes;
    var value = attributes.value,
        citation = attributes.citation,
        align = attributes.align;
    return createElement("blockquote", {
      className: "align".concat(align)
    }, createElement(RichText.Content, {
      value: value,
      multiline: true
    }), !RichText.isEmpty(citation) && createElement(RichText.Content, {
      tagName: "footer",
      value: citation
    }));
  }
}];
export default deprecated;
//# sourceMappingURL=deprecated.js.map