"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _keycodes = require("@wordpress/keycodes");

var _icons = require("@wordpress/icons");

var _blocks = require("@wordpress/blocks");

var _colorEdit = _interopRequireDefault(require("./color-edit"));

var _colorProps = _interopRequireDefault(require("./color-props"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var NEW_TAB_REL = 'noreferrer noopener';
var MIN_BORDER_RADIUS_VALUE = 0;
var MAX_BORDER_RADIUS_VALUE = 50;
var INITIAL_BORDER_RADIUS_POSITION = 5;
var EMPTY_ARRAY = [];

function BorderPanel(_ref) {
  var _ref$borderRadius = _ref.borderRadius,
      borderRadius = _ref$borderRadius === void 0 ? '' : _ref$borderRadius,
      setAttributes = _ref.setAttributes;
  var initialBorderRadius = borderRadius;
  var setBorderRadius = (0, _element.useCallback)(function (newBorderRadius) {
    if (newBorderRadius === undefined) setAttributes({
      borderRadius: initialBorderRadius
    });else setAttributes({
      borderRadius: newBorderRadius
    });
  }, [setAttributes]);
  return (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Border settings')
  }, (0, _element.createElement)(_components.RangeControl, {
    value: borderRadius,
    label: (0, _i18n.__)('Border radius'),
    min: MIN_BORDER_RADIUS_VALUE,
    max: MAX_BORDER_RADIUS_VALUE,
    initialPosition: INITIAL_BORDER_RADIUS_POSITION,
    allowReset: true,
    onChange: setBorderRadius
  }));
}

function URLPicker(_ref2) {
  var _ref4;

  var isSelected = _ref2.isSelected,
      url = _ref2.url,
      setAttributes = _ref2.setAttributes,
      opensInNewTab = _ref2.opensInNewTab,
      onToggleOpenInNewTab = _ref2.onToggleOpenInNewTab;

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isURLPickerOpen = _useState2[0],
      setIsURLPickerOpen = _useState2[1];

  var urlIsSet = !!url;
  var urlIsSetandSelected = urlIsSet && isSelected;

  var openLinkControl = function openLinkControl() {
    setIsURLPickerOpen(true);
    return false; // prevents default behaviour for event
  };

  var unlinkButton = function unlinkButton() {
    setAttributes({
      url: undefined,
      linkTarget: undefined,
      rel: undefined
    });
    setIsURLPickerOpen(false);
  };

  var linkControl = (isURLPickerOpen || urlIsSetandSelected) && (0, _element.createElement)(_components.Popover, {
    position: "bottom center",
    onClose: function onClose() {
      return setIsURLPickerOpen(false);
    }
  }, (0, _element.createElement)(_blockEditor.__experimentalLinkControl, {
    className: "wp-block-navigation-link__inline-link-input",
    value: {
      url: url,
      opensInNewTab: opensInNewTab
    },
    onChange: function onChange(_ref3) {
      var _ref3$url = _ref3.url,
          newURL = _ref3$url === void 0 ? '' : _ref3$url,
          newOpensInNewTab = _ref3.opensInNewTab;
      setAttributes({
        url: newURL
      });

      if (opensInNewTab !== newOpensInNewTab) {
        onToggleOpenInNewTab(newOpensInNewTab);
      }
    }
  }));
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, null, !urlIsSet && (0, _element.createElement)(_components.ToolbarButton, {
    name: "link",
    icon: _icons.link,
    title: (0, _i18n.__)('Link'),
    shortcut: _keycodes.displayShortcut.primary('k'),
    onClick: openLinkControl
  }), urlIsSetandSelected && (0, _element.createElement)(_components.ToolbarButton, {
    name: "link",
    icon: _icons.linkOff,
    title: (0, _i18n.__)('Unlink'),
    shortcut: _keycodes.displayShortcut.primaryShift('k'),
    onClick: unlinkButton,
    isActive: true
  }))), isSelected && (0, _element.createElement)(_components.KeyboardShortcuts, {
    bindGlobal: true,
    shortcuts: (_ref4 = {}, (0, _defineProperty2.default)(_ref4, _keycodes.rawShortcut.primary('k'), openLinkControl), (0, _defineProperty2.default)(_ref4, _keycodes.rawShortcut.primaryShift('k'), unlinkButton), _ref4)
  }), linkControl);
}

function ButtonEdit(props) {
  var attributes = props.attributes,
      setAttributes = props.setAttributes,
      className = props.className,
      isSelected = props.isSelected,
      onReplace = props.onReplace,
      mergeBlocks = props.mergeBlocks;
  var borderRadius = attributes.borderRadius,
      linkTarget = attributes.linkTarget,
      placeholder = attributes.placeholder,
      rel = attributes.rel,
      text = attributes.text,
      url = attributes.url;
  var onSetLinkRel = (0, _element.useCallback)(function (value) {
    setAttributes({
      rel: value
    });
  }, [setAttributes]);
  var colors = (0, _blockEditor.__experimentalUseEditorFeature)('color.palette') || EMPTY_ARRAY;
  var onToggleOpenInNewTab = (0, _element.useCallback)(function (value) {
    var newLinkTarget = value ? '_blank' : undefined;
    var updatedRel = rel;

    if (newLinkTarget && !rel) {
      updatedRel = NEW_TAB_REL;
    } else if (!newLinkTarget && rel === NEW_TAB_REL) {
      updatedRel = undefined;
    }

    setAttributes({
      linkTarget: newLinkTarget,
      rel: updatedRel
    });
  }, [rel, setAttributes]);
  var colorProps = (0, _colorProps.default)(attributes, colors, true);
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_colorEdit.default, props), (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)(_blockEditor.RichText, {
    placeholder: placeholder || (0, _i18n.__)('Add text…'),
    value: text,
    onChange: function onChange(value) {
      return setAttributes({
        text: value
      });
    },
    withoutInteractiveFormatting: true,
    className: (0, _classnames.default)(className, 'wp-block-button__link', colorProps.className, {
      'no-border-radius': borderRadius === 0
    }),
    style: _objectSpread({
      borderRadius: borderRadius ? borderRadius + 'px' : undefined
    }, colorProps.style),
    onSplit: function onSplit(value) {
      return (0, _blocks.createBlock)('core/button', _objectSpread(_objectSpread({}, attributes), {}, {
        text: value
      }));
    },
    onReplace: onReplace,
    onMerge: mergeBlocks,
    identifier: "text"
  })), (0, _element.createElement)(URLPicker, {
    url: url,
    setAttributes: setAttributes,
    isSelected: isSelected,
    opensInNewTab: linkTarget === '_blank',
    onToggleOpenInNewTab: onToggleOpenInNewTab
  }), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(BorderPanel, {
    borderRadius: borderRadius,
    setAttributes: setAttributes
  }), (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Link settings')
  }, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Open in new tab'),
    onChange: onToggleOpenInNewTab,
    checked: linkTarget === '_blank'
  }), (0, _element.createElement)(_components.TextControl, {
    label: (0, _i18n.__)('Link rel'),
    value: rel || '',
    onChange: onSetLinkRel
  }))));
}

var _default = ButtonEdit;
exports.default = _default;
//# sourceMappingURL=edit.js.map