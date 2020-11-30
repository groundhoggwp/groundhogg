import _extends from "@babel/runtime/helpers/esm/extends";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Popover } from '@wordpress/components';
import { chevronDown } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import LinkViewer from './link-viewer';
import LinkEditor from './link-editor';

function URLPopover(_ref) {
  var additionalControls = _ref.additionalControls,
      children = _ref.children,
      renderSettings = _ref.renderSettings,
      _ref$position = _ref.position,
      position = _ref$position === void 0 ? 'bottom center' : _ref$position,
      _ref$focusOnMount = _ref.focusOnMount,
      focusOnMount = _ref$focusOnMount === void 0 ? 'firstElement' : _ref$focusOnMount,
      popoverProps = _objectWithoutProperties(_ref, ["additionalControls", "children", "renderSettings", "position", "focusOnMount"]);

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isSettingsExpanded = _useState2[0],
      setIsSettingsExpanded = _useState2[1];

  var showSettings = !!renderSettings && isSettingsExpanded;

  var toggleSettingsVisibility = function toggleSettingsVisibility() {
    setIsSettingsExpanded(!isSettingsExpanded);
  };

  return createElement(Popover, _extends({
    className: "block-editor-url-popover",
    focusOnMount: focusOnMount,
    position: position
  }, popoverProps), createElement("div", {
    className: "block-editor-url-popover__input-container"
  }, createElement("div", {
    className: "block-editor-url-popover__row"
  }, children, !!renderSettings && createElement(Button, {
    className: "block-editor-url-popover__settings-toggle",
    icon: chevronDown,
    label: __('Link settings'),
    onClick: toggleSettingsVisibility,
    "aria-expanded": isSettingsExpanded
  })), showSettings && createElement("div", {
    className: "block-editor-url-popover__row block-editor-url-popover__settings"
  }, renderSettings())), additionalControls && !showSettings && createElement("div", {
    className: "block-editor-url-popover__additional-controls"
  }, additionalControls));
}

URLPopover.LinkEditor = LinkEditor;
URLPopover.LinkViewer = LinkViewer;
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/url-popover/README.md
 */

export default URLPopover;
//# sourceMappingURL=index.js.map