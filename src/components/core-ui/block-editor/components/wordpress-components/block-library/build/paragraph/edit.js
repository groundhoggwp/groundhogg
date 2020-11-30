"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function getComputedStyle(node, pseudo) {
  return node.ownerDocument.defaultView.getComputedStyle(node, pseudo);
}

var querySelector = window.document.querySelector.bind(document);
var name = 'core/paragraph';
var PARAGRAPH_DROP_CAP_SELECTOR = 'p.has-drop-cap';

function ParagraphRTLToolbar(_ref) {
  var direction = _ref.direction,
      setDirection = _ref.setDirection;
  var isRTL = (0, _data.useSelect)(function (select) {
    return !!select('core/block-editor').getSettings().isRTL;
  }, []);
  return isRTL && (0, _element.createElement)(_components.ToolbarGroup, {
    controls: [{
      icon: _icons.formatLtr,
      title: (0, _i18n._x)('Left to right', 'editor button'),
      isActive: direction === 'ltr',
      onClick: function onClick() {
        setDirection(direction === 'ltr' ? undefined : 'ltr');
      }
    }]
  });
}

function useDropCap(isDropCap, fontSize, styleFontSize) {
  var isDisabled = !(0, _blockEditor.__experimentalUseEditorFeature)('typography.dropCap');

  var _useState = (0, _element.useState)(),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      minimumHeight = _useState2[0],
      setMinimumHeight = _useState2[1];

  var _useSelect = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getSettings();
  }),
      fontSizes = _useSelect.fontSizes;

  var fontSizeObject = (0, _blockEditor.getFontSize)(fontSizes, fontSize, styleFontSize);
  (0, _element.useEffect)(function () {
    if (isDisabled) {
      return;
    }

    var element = querySelector(PARAGRAPH_DROP_CAP_SELECTOR);

    if (isDropCap && element) {
      setMinimumHeight(getComputedStyle(element, 'first-letter').lineHeight);
    } else if (minimumHeight) {
      setMinimumHeight(undefined);
    }
  }, [isDisabled, isDropCap, minimumHeight, setMinimumHeight, fontSizeObject.size]);
  return [!isDisabled, minimumHeight];
}

function ParagraphBlock(_ref2) {
  var attributes = _ref2.attributes,
      mergeBlocks = _ref2.mergeBlocks,
      onReplace = _ref2.onReplace,
      onRemove = _ref2.onRemove,
      setAttributes = _ref2.setAttributes;
  var align = attributes.align,
      content = attributes.content,
      direction = attributes.direction,
      dropCap = attributes.dropCap,
      placeholder = attributes.placeholder,
      fontSize = attributes.fontSize,
      style = attributes.style;

  var _useDropCap = useDropCap(dropCap, fontSize, style === null || style === void 0 ? void 0 : style.fontSize),
      _useDropCap2 = (0, _slicedToArray2.default)(_useDropCap, 2),
      isDropCapEnabled = _useDropCap2[0],
      dropCapMinimumHeight = _useDropCap2[1];

  var styles = {
    direction: direction,
    minHeight: dropCapMinimumHeight
  };
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({
      'has-drop-cap': dropCap
    }, "has-text-align-".concat(align), align)),
    style: styles
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: align,
    onChange: function onChange(newAlign) {
      return setAttributes({
        align: newAlign
      });
    }
  }), (0, _element.createElement)(ParagraphRTLToolbar, {
    direction: direction,
    setDirection: function setDirection(newDirection) {
      return setAttributes({
        direction: newDirection
      });
    }
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, isDropCapEnabled && (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Text settings')
  }, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Drop cap'),
    checked: !!dropCap,
    onChange: function onChange() {
      return setAttributes({
        dropCap: !dropCap
      });
    },
    help: dropCap ? (0, _i18n.__)('Showing large initial letter.') : (0, _i18n.__)('Toggle to show a large initial letter.')
  }))), (0, _element.createElement)(_blockEditor.RichText, (0, _extends2.default)({
    identifier: "content",
    tagName: "p"
  }, blockWrapperProps, {
    value: content,
    onChange: function onChange(newContent) {
      return setAttributes({
        content: newContent
      });
    },
    onSplit: function onSplit(value) {
      if (!value) {
        return (0, _blocks.createBlock)(name);
      }

      return (0, _blocks.createBlock)(name, _objectSpread(_objectSpread({}, attributes), {}, {
        content: value
      }));
    },
    onMerge: mergeBlocks,
    onReplace: onReplace,
    onRemove: onRemove,
    "aria-label": content ? (0, _i18n.__)('Paragraph block') : (0, _i18n.__)('Empty block; start writing or type forward slash to choose a block'),
    placeholder: placeholder || (0, _i18n.__)('Start writing or type / to choose a block'),
    __unstableEmbedURLOnPaste: true,
    __unstableAllowPrefixTransformations: true
  })));
}

var _default = ParagraphBlock;
exports.default = _default;
//# sourceMappingURL=edit.js.map