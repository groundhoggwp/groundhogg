"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ListEdit;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _richText = require("@wordpress/rich-text");

var _icons = require("@wordpress/icons");

var _data = require("@wordpress/data");

var _ = require("./");

var _orderedListSettings = _interopRequireDefault(require("./ordered-list-settings"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function ListEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      mergeBlocks = _ref.mergeBlocks,
      onReplace = _ref.onReplace,
      isSelected = _ref.isSelected;
  var ordered = attributes.ordered,
      values = attributes.values,
      type = attributes.type,
      reversed = attributes.reversed,
      start = attributes.start;
  var tagName = ordered ? 'ol' : 'ul';
  var isRTL = (0, _data.useSelect)(function (select) {
    return !!select('core/block-editor').getSettings().isRTL;
  }, []);

  var controls = function controls(_ref2) {
    var value = _ref2.value,
        onChange = _ref2.onChange,
        onFocus = _ref2.onFocus;
    return (0, _element.createElement)(_element.Fragment, null, isSelected && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.RichTextShortcut, {
      type: "primary",
      character: "[",
      onUse: function onUse() {
        onChange((0, _richText.__unstableOutdentListItems)(value));
      }
    }), (0, _element.createElement)(_blockEditor.RichTextShortcut, {
      type: "primary",
      character: "]",
      onUse: function onUse() {
        onChange((0, _richText.__unstableIndentListItems)(value, {
          type: tagName
        }));
      }
    }), (0, _element.createElement)(_blockEditor.RichTextShortcut, {
      type: "primary",
      character: "m",
      onUse: function onUse() {
        onChange((0, _richText.__unstableIndentListItems)(value, {
          type: tagName
        }));
      }
    }), (0, _element.createElement)(_blockEditor.RichTextShortcut, {
      type: "primaryShift",
      character: "m",
      onUse: function onUse() {
        onChange((0, _richText.__unstableOutdentListItems)(value));
      }
    })), (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, {
      controls: [{
        icon: isRTL ? _icons.formatListBulletsRTL : _icons.formatListBullets,
        title: (0, _i18n.__)('Convert to unordered list'),
        isActive: (0, _richText.__unstableIsActiveListType)(value, 'ul', tagName),
        onClick: function onClick() {
          onChange((0, _richText.__unstableChangeListType)(value, {
            type: 'ul'
          }));
          onFocus();

          if ((0, _richText.__unstableIsListRootSelected)(value)) {
            setAttributes({
              ordered: false
            });
          }
        }
      }, {
        icon: isRTL ? _icons.formatListNumberedRTL : _icons.formatListNumbered,
        title: (0, _i18n.__)('Convert to ordered list'),
        isActive: (0, _richText.__unstableIsActiveListType)(value, 'ol', tagName),
        onClick: function onClick() {
          onChange((0, _richText.__unstableChangeListType)(value, {
            type: 'ol'
          }));
          onFocus();

          if ((0, _richText.__unstableIsListRootSelected)(value)) {
            setAttributes({
              ordered: true
            });
          }
        }
      }, {
        icon: isRTL ? _icons.formatOutdentRTL : _icons.formatOutdent,
        title: (0, _i18n.__)('Outdent list item'),
        shortcut: (0, _i18n._x)('Backspace', 'keyboard key'),
        isDisabled: !(0, _richText.__unstableCanOutdentListItems)(value),
        onClick: function onClick() {
          onChange((0, _richText.__unstableOutdentListItems)(value));
          onFocus();
        }
      }, {
        icon: isRTL ? _icons.formatIndentRTL : _icons.formatIndent,
        title: (0, _i18n.__)('Indent list item'),
        shortcut: (0, _i18n._x)('Space', 'keyboard key'),
        isDisabled: !(0, _richText.__unstableCanIndentListItems)(value),
        onClick: function onClick() {
          onChange((0, _richText.__unstableIndentListItems)(value, {
            type: tagName
          }));
          onFocus();
        }
      }]
    })));
  };

  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.RichText, (0, _extends2.default)({
    identifier: "values",
    multiline: "li",
    __unstableMultilineRootTag: tagName,
    tagName: tagName,
    onChange: function onChange(nextValues) {
      return setAttributes({
        values: nextValues
      });
    },
    value: values,
    placeholder: (0, _i18n.__)('Write list…'),
    onMerge: mergeBlocks,
    onSplit: function onSplit(value) {
      return (0, _blocks.createBlock)(_.name, _objectSpread(_objectSpread({}, attributes), {}, {
        values: value
      }));
    },
    __unstableOnSplitMiddle: function __unstableOnSplitMiddle() {
      return (0, _blocks.createBlock)('core/paragraph');
    },
    onReplace: onReplace,
    onRemove: function onRemove() {
      return onReplace([]);
    },
    start: start,
    reversed: reversed,
    type: type
  }, blockWrapperProps), controls), ordered && (0, _element.createElement)(_orderedListSettings.default, {
    setAttributes: setAttributes,
    ordered: ordered,
    reversed: reversed,
    start: start
  }));
}
//# sourceMappingURL=edit.js.map