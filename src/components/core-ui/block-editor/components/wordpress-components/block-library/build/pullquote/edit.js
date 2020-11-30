"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _classnames3 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _blocks = require("@wordpress/blocks");

var _figure = require("./figure");

var _blockquote = require("./blockquote");

var _shared = require("./shared");

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var PullQuoteEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(PullQuoteEdit, _Component);

  var _super = _createSuper(PullQuoteEdit);

  function PullQuoteEdit(props) {
    var _this;

    (0, _classCallCheck2.default)(this, PullQuoteEdit);
    _this = _super.call(this, props);
    _this.wasTextColorAutomaticallyComputed = false;
    _this.pullQuoteMainColorSetter = _this.pullQuoteMainColorSetter.bind((0, _assertThisInitialized2.default)(_this));
    _this.pullQuoteTextColorSetter = _this.pullQuoteTextColorSetter.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(PullQuoteEdit, [{
    key: "pullQuoteMainColorSetter",
    value: function pullQuoteMainColorSetter(colorValue) {
      var _this$props = this.props,
          colorUtils = _this$props.colorUtils,
          textColor = _this$props.textColor,
          setAttributes = _this$props.setAttributes,
          setTextColor = _this$props.setTextColor,
          setMainColor = _this$props.setMainColor,
          className = _this$props.className;
      var isSolidColorStyle = (0, _lodash.includes)(className, _shared.SOLID_COLOR_CLASS);
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

      if (attributes.mainColor && !(0, _lodash.includes)(className, _shared.SOLID_COLOR_CLASS) && (0, _lodash.includes)(prevProps.className, _shared.SOLID_COLOR_CLASS)) {
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
      var isSolidColorStyle = (0, _lodash.includes)(className, _shared.SOLID_COLOR_CLASS);
      var figureStyles = isSolidColorStyle ? {
        backgroundColor: mainColor.color
      } : {
        borderColor: mainColor.color
      };
      var figureClasses = (0, _classnames3.default)(className, (0, _defineProperty2.default)({
        'has-background': isSolidColorStyle && mainColor.color
      }, mainColor.class, isSolidColorStyle && mainColor.class));
      var blockquoteStyles = {
        color: textColor.color
      };
      var blockquoteClasses = textColor.color && (0, _classnames3.default)('has-text-color', (0, _defineProperty2.default)({}, textColor.class, textColor.class));
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_figure.Figure, {
        style: figureStyles,
        className: figureClasses
      }, (0, _element.createElement)(_blockquote.BlockQuote, {
        style: blockquoteStyles,
        className: blockquoteClasses
      }, (0, _element.createElement)(_blockEditor.RichText, {
        identifier: "value",
        multiline: true,
        value: value,
        onChange: function onChange(nextValue) {
          return setAttributes({
            value: nextValue
          });
        },
        placeholder: // translators: placeholder text used for the quote
        (0, _i18n.__)('Write quote…'),
        textAlign: "center"
      }), (!_blockEditor.RichText.isEmpty(citation) || isSelected) && (0, _element.createElement)(_blockEditor.RichText, {
        identifier: "citation",
        value: citation,
        placeholder: // translators: placeholder text used for the citation
        (0, _i18n.__)('Write citation…'),
        onChange: function onChange(nextCitation) {
          return setAttributes({
            citation: nextCitation
          });
        },
        className: "wp-block-pullquote__citation",
        __unstableMobileNoFocusOnMount: true,
        textAlign: "center",
        __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
          return insertBlocksAfter((0, _blocks.createBlock)('core/paragraph'));
        }
      }))), _element.Platform.OS === 'web' && (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_blockEditor.PanelColorSettings, {
        title: (0, _i18n.__)('Color settings'),
        colorSettings: [{
          value: mainColor.color,
          onChange: this.pullQuoteMainColorSetter,
          label: (0, _i18n.__)('Main color')
        }, {
          value: textColor.color,
          onChange: this.pullQuoteTextColorSetter,
          label: (0, _i18n.__)('Text color')
        }]
      }, isSolidColorStyle && (0, _element.createElement)(_blockEditor.ContrastChecker, (0, _extends2.default)({
        textColor: textColor.color,
        backgroundColor: mainColor.color
      }, {
        isLargeText: false
      })))));
    }
  }]);
  return PullQuoteEdit;
}(_element.Component);

var _default = (0, _blockEditor.withColors)({
  mainColor: 'background-color',
  textColor: 'color'
})(PullQuoteEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map