import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement, Fragment } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { includes } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { Component, Platform } from '@wordpress/element';
import { RichText, ContrastChecker, InspectorControls, withColors, PanelColorSettings } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import { Figure } from './figure';
import { BlockQuote } from './blockquote';
/**
 * Internal dependencies
 */

import { SOLID_COLOR_CLASS } from './shared';

var PullQuoteEdit = /*#__PURE__*/function (_Component) {
  _inherits(PullQuoteEdit, _Component);

  var _super = _createSuper(PullQuoteEdit);

  function PullQuoteEdit(props) {
    var _this;

    _classCallCheck(this, PullQuoteEdit);

    _this = _super.call(this, props);
    _this.wasTextColorAutomaticallyComputed = false;
    _this.pullQuoteMainColorSetter = _this.pullQuoteMainColorSetter.bind(_assertThisInitialized(_this));
    _this.pullQuoteTextColorSetter = _this.pullQuoteTextColorSetter.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(PullQuoteEdit, [{
    key: "pullQuoteMainColorSetter",
    value: function pullQuoteMainColorSetter(colorValue) {
      var _this$props = this.props,
          colorUtils = _this$props.colorUtils,
          textColor = _this$props.textColor,
          setAttributes = _this$props.setAttributes,
          setTextColor = _this$props.setTextColor,
          setMainColor = _this$props.setMainColor,
          className = _this$props.className;
      var isSolidColorStyle = includes(className, SOLID_COLOR_CLASS);
      var needTextColor = !textColor.color || this.wasTextColorAutomaticallyComputed;
      var shouldSetTextColor = isSolidColorStyle && needTextColor;

      if (isSolidColorStyle) {
        // If we use the solid color style, set the color using the normal mechanism.
        setMainColor(colorValue);
      } else {
        // If we use the default style, set the color as a custom color to force the usage of an inline style.
        // Default style uses a border color for which classes are not available.
        setAttributes({
          customMainColor: colorValue
        });
      }

      if (shouldSetTextColor) {
        if (colorValue) {
          this.wasTextColorAutomaticallyComputed = true;
          setTextColor(colorUtils.getMostReadableColor(colorValue));
        } else if (this.wasTextColorAutomaticallyComputed) {
          // We have to unset our previously computed text color on unsetting the main color.
          this.wasTextColorAutomaticallyComputed = false;
          setTextColor();
        }
      }
    }
  }, {
    key: "pullQuoteTextColorSetter",
    value: function pullQuoteTextColorSetter(colorValue) {
      var setTextColor = this.props.setTextColor;
      setTextColor(colorValue);
      this.wasTextColorAutomaticallyComputed = false;
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props2 = this.props,
          attributes = _this$props2.attributes,
          className = _this$props2.className,
          mainColor = _this$props2.mainColor,
          setAttributes = _this$props2.setAttributes; // If the block includes a named color and we switched from the
      // solid color style to the default style.

      if (attributes.mainColor && !includes(className, SOLID_COLOR_CLASS) && includes(prevProps.className, SOLID_COLOR_CLASS)) {
        // Remove the named color, and set the color as a custom color.
        // This is done because named colors use classes, in the default style we use a border color,
        // and themes don't set classes for border colors.
        setAttributes({
          mainColor: undefined,
          customMainColor: mainColor.color
        });
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props3 = this.props,
          attributes = _this$props3.attributes,
          mainColor = _this$props3.mainColor,
          textColor = _this$props3.textColor,
          setAttributes = _this$props3.setAttributes,
          isSelected = _this$props3.isSelected,
          className = _this$props3.className,
          insertBlocksAfter = _this$props3.insertBlocksAfter;
      var value = attributes.value,
          citation = attributes.citation;
      var isSolidColorStyle = includes(className, SOLID_COLOR_CLASS);
      var figureStyles = isSolidColorStyle ? {
        backgroundColor: mainColor.color
      } : {
        borderColor: mainColor.color
      };
      var figureClasses = classnames(className, _defineProperty({
        'has-background': isSolidColorStyle && mainColor.color
      }, mainColor.class, isSolidColorStyle && mainColor.class));
      var blockquoteStyles = {
        color: textColor.color
      };
      var blockquoteClasses = textColor.color && classnames('has-text-color', _defineProperty({}, textColor.class, textColor.class));
      return createElement(Fragment, null, createElement(Figure, {
        style: figureStyles,
        className: figureClasses
      }, createElement(BlockQuote, {
        style: blockquoteStyles,
        className: blockquoteClasses
      }, createElement(RichText, {
        identifier: "value",
        multiline: true,
        value: value,
        onChange: function onChange(nextValue) {
          return setAttributes({
            value: nextValue
          });
        },
        placeholder: // translators: placeholder text used for the quote
        __('Write quote…'),
        textAlign: "center"
      }), (!RichText.isEmpty(citation) || isSelected) && createElement(RichText, {
        identifier: "citation",
        value: citation,
        placeholder: // translators: placeholder text used for the citation
        __('Write citation…'),
        onChange: function onChange(nextCitation) {
          return setAttributes({
            citation: nextCitation
          });
        },
        className: "wp-block-pullquote__citation",
        __unstableMobileNoFocusOnMount: true,
        textAlign: "center",
        __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
          return insertBlocksAfter(createBlock('core/paragraph'));
        }
      }))), Platform.OS === 'web' && createElement(InspectorControls, null, createElement(PanelColorSettings, {
        title: __('Color settings'),
        colorSettings: [{
          value: mainColor.color,
          onChange: this.pullQuoteMainColorSetter,
          label: __('Main color')
        }, {
          value: textColor.color,
          onChange: this.pullQuoteTextColorSetter,
          label: __('Text color')
        }]
      }, isSolidColorStyle && createElement(ContrastChecker, _extends({
        textColor: textColor.color,
        backgroundColor: mainColor.color
      }, {
        isLargeText: false
      })))));
    }
  }]);

  return PullQuoteEdit;
}(Component);

export default withColors({
  mainColor: 'background-color',
  textColor: 'color'
})(PullQuoteEdit);
//# sourceMappingURL=edit.js.map