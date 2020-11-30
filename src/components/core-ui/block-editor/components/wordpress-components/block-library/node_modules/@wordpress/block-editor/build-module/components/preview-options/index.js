import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useViewportMatch } from '@wordpress/compose';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { check } from '@wordpress/icons';
export default function PreviewOptions(_ref) {
  var children = _ref.children,
      className = _ref.className,
      _ref$isEnabled = _ref.isEnabled,
      isEnabled = _ref$isEnabled === void 0 ? true : _ref$isEnabled,
      deviceType = _ref.deviceType,
      setDeviceType = _ref.setDeviceType;
  var isMobile = useViewportMatch('small', '<');
  if (isMobile) return null;
  var popoverProps = {
    className: classnames(className, 'block-editor-post-preview__dropdown-content'),
    position: 'bottom left'
  };
  var toggleProps = {
    isTertiary: true,
    className: 'block-editor-post-preview__button-toggle',
    disabled: !isEnabled,

    /* translators: button label text should, if possible, be under 16 characters. */
    children: __('Preview')
  };
  return createElement(DropdownMenu, {
    className: "block-editor-post-preview__dropdown",
    popoverProps: popoverProps,
    toggleProps: toggleProps,
    icon: null
  }, function () {
    return createElement(Fragment, null, createElement(MenuGroup, null, createElement(MenuItem, {
      className: "block-editor-post-preview__button-resize",
      onClick: function onClick() {
        return setDeviceType('Desktop');
      },
      icon: deviceType === 'Desktop' && check
    }, __('Desktop')), createElement(MenuItem, {
      className: "block-editor-post-preview__button-resize",
      onClick: function onClick() {
        return setDeviceType('Tablet');
      },
      icon: deviceType === 'Tablet' && check
    }, __('Tablet')), createElement(MenuItem, {
      className: "block-editor-post-preview__button-resize",
      onClick: function onClick() {
        return setDeviceType('Mobile');
      },
      icon: deviceType === 'Mobile' && check
    }, __('Mobile'))), children);
  });
}
//# sourceMappingURL=index.js.map