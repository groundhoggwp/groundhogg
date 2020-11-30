"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PreviewOptions;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _compose = require("@wordpress/compose");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function PreviewOptions(_ref) {
  var children = _ref.children,
      className = _ref.className,
      _ref$isEnabled = _ref.isEnabled,
      isEnabled = _ref$isEnabled === void 0 ? true : _ref$isEnabled,
      deviceType = _ref.deviceType,
      setDeviceType = _ref.setDeviceType;
  var isMobile = (0, _compose.useViewportMatch)('small', '<');
  if (isMobile) return null;
  var popoverProps = {
    className: (0, _classnames.default)(className, 'block-editor-post-preview__dropdown-content'),
    position: 'bottom left'
  };
  var toggleProps = {
    isTertiary: true,
    className: 'block-editor-post-preview__button-toggle',
    disabled: !isEnabled,

    /* translators: button label text should, if possible, be under 16 characters. */
    children: (0, _i18n.__)('Preview')
  };
  return (0, _element.createElement)(_components.DropdownMenu, {
    className: "block-editor-post-preview__dropdown",
    popoverProps: popoverProps,
    toggleProps: toggleProps,
    icon: null
  }, function () {
    return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.MenuGroup, null, (0, _element.createElement)(_components.MenuItem, {
      className: "block-editor-post-preview__button-resize",
      onClick: function onClick() {
        return setDeviceType('Desktop');
      },
      icon: deviceType === 'Desktop' && _icons.check
    }, (0, _i18n.__)('Desktop')), (0, _element.createElement)(_components.MenuItem, {
      className: "block-editor-post-preview__button-resize",
      onClick: function onClick() {
        return setDeviceType('Tablet');
      },
      icon: deviceType === 'Tablet' && _icons.check
    }, (0, _i18n.__)('Tablet')), (0, _element.createElement)(_components.MenuItem, {
      className: "block-editor-post-preview__button-resize",
      onClick: function onClick() {
        return setDeviceType('Mobile');
      },
      icon: deviceType === 'Mobile' && _icons.check
    }, (0, _i18n.__)('Mobile'))), children);
  });
}
//# sourceMappingURL=index.js.map