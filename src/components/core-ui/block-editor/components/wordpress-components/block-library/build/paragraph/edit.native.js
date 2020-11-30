"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var name = 'core/paragraph';

function ParagraphBlock(_ref) {
  var attributes = _ref.attributes,
      mergeBlocks = _ref.mergeBlocks,
      onReplace = _ref.onReplace,
      setAttributes = _ref.setAttributes,
      mergedStyle = _ref.mergedStyle,
      style = _ref.style;
  var isRTL = (0, _data.useSelect)(function (select) {
    return !!select('core/block-editor').getSettings().isRTL;
  }, []);
  var align = attributes.align,
      content = attributes.content,
      placeholder = attributes.placeholder;

  var styles = _objectSpread(_objectSpread({}, mergedStyle), style);

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: align,
    isRTL: isRTL,
    onChange: function onChange(nextAlign) {
      setAttributes({
        align: nextAlign
      });
    }
  })), (0, _element.createElement)(_blockEditor.RichText, {
    identifier: "content",
    tagName: "p",
    value: content,
    deleteEnter: true,
    style: styles,
    onChange: function onChange(nextContent) {
      setAttributes({
        content: nextContent
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
    onRemove: onReplace ? function () {
      return onReplace([]);
    } : undefined,
    placeholder: placeholder || (0, _i18n.__)('Start writing…'),
    textAlign: align
  }));
}

var _default = ParagraphBlock;
exports.default = _default;
//# sourceMappingURL=edit.native.js.map