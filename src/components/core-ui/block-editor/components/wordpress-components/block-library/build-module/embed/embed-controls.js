import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToolbarButton, PanelBody, ToggleControl, ToolbarGroup } from '@wordpress/components';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { edit } from '@wordpress/icons';

var EmbedControls = function EmbedControls(_ref) {
  var blockSupportsResponsive = _ref.blockSupportsResponsive,
      showEditButton = _ref.showEditButton,
      themeSupportsResponsive = _ref.themeSupportsResponsive,
      allowResponsive = _ref.allowResponsive,
      getResponsiveHelp = _ref.getResponsiveHelp,
      toggleResponsive = _ref.toggleResponsive,
      switchBackToURLInput = _ref.switchBackToURLInput;
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(ToolbarGroup, null, showEditButton && createElement(ToolbarButton, {
    className: "components-toolbar__control",
    label: __('Edit URL'),
    icon: edit,
    onClick: switchBackToURLInput
  }))), themeSupportsResponsive && blockSupportsResponsive && createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Media settings'),
    className: "blocks-responsive"
  }, createElement(ToggleControl, {
    label: __('Resize for smaller devices'),
    checked: allowResponsive,
    help: getResponsiveHelp,
    onChange: toggleResponsive
  }))));
};

export default EmbedControls;
//# sourceMappingURL=embed-controls.js.map