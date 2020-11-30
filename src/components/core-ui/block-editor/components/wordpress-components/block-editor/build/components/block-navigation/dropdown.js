"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _keyboardShortcuts = require("@wordpress/keyboard-shortcuts");

var _ = _interopRequireDefault(require("./"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var MenuIcon = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  width: "24",
  height: "24"
}, (0, _element.createElement)(_components.Path, {
  d: "M13.8 5.2H3v1.5h10.8V5.2zm-3.6 12v1.5H21v-1.5H10.2zm7.2-6H6.6v1.5h10.8v-1.5z"
}));

function BlockNavigationDropdownToggle(_ref) {
  var isEnabled = _ref.isEnabled,
      onToggle = _ref.onToggle,
      isOpen = _ref.isOpen,
      innerRef = _ref.innerRef,
      props = (0, _objectWithoutProperties2.default)(_ref, ["isEnabled", "onToggle", "isOpen", "innerRef"]);
  (0, _keyboardShortcuts.useShortcut)('core/edit-post/toggle-block-navigation', (0, _element.useCallback)(onToggle, [onToggle]), {
    bindGlobal: true,
    isDisabled: !isEnabled
  });
  var shortcut = (0, _data.useSelect)(function (select) {
    return select('core/keyboard-shortcuts').getShortcutRepresentation('core/edit-post/toggle-block-navigation');
  }, []);
  return (0, _element.createElement)(_components.Button, (0, _extends2.default)({}, props, {
    ref: innerRef,
    icon: MenuIcon,
    "aria-expanded": isOpen,
    "aria-haspopup": "true",
    onClick: isEnabled ? onToggle : undefined
    /* translators: button label text should, if possible, be under 16 characters. */
    ,
    label: (0, _i18n.__)('Outline'),
    className: "block-editor-block-navigation",
    shortcut: shortcut,
    "aria-disabled": !isEnabled
  }));
}

function BlockNavigationDropdown(_ref2, ref) {
  var isDisabled = _ref2.isDisabled,
      __experimentalFeatures = _ref2.__experimentalFeatures,
      props = (0, _objectWithoutProperties2.default)(_ref2, ["isDisabled", "__experimentalFeatures"]);
  var hasBlocks = (0, _data.useSelect)(function (select) {
    return !!select('core/block-editor').getBlockCount();
  }, []);
  var isEnabled = hasBlocks && !isDisabled;
  return (0, _element.createElement)(_components.Dropdown, {
    contentClassName: "block-editor-block-navigation__popover",
    position: "bottom right",
    renderToggle: function renderToggle(_ref3) {
      var isOpen = _ref3.isOpen,
          onToggle = _ref3.onToggle;
      return (0, _element.createElement)(BlockNavigationDropdownToggle, (0, _extends2.default)({}, props, {
        innerRef: ref,
        isOpen: isOpen,
        onToggle: onToggle,
        isEnabled: isEnabled
      }));
    },
    renderContent: function renderContent(_ref4) {
      var onClose = _ref4.onClose;
      return (0, _element.createElement)(_.default, {
        onSelect: onClose,
        __experimentalFeatures: __experimentalFeatures
      });
    }
  });
}

var _default = (0, _element.forwardRef)(BlockNavigationDropdown);

exports.default = _default;
//# sourceMappingURL=dropdown.js.map