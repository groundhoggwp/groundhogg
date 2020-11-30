import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Fragment } from '@wordpress/element';
import { InnerBlocks, __experimentalUseBlockWrapperProps as useBlockWrapperProps, InspectorControls } from '@wordpress/block-editor';
import { ToggleControl, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
var ALLOWED_BLOCKS = ['core/social-link']; // Template contains the links that show when start.

var TEMPLATE = [['core/social-link', {
  service: 'wordpress',
  url: 'https://wordpress.org'
}], ['core/social-link', {
  service: 'facebook'
}], ['core/social-link', {
  service: 'twitter'
}], ['core/social-link', {
  service: 'instagram'
}], ['core/social-link', {
  service: 'linkedin'
}], ['core/social-link', {
  service: 'youtube'
}]];
export function SocialLinksEdit(props) {
  var openInNewTab = props.attributes.openInNewTab,
      setAttributes = props.setAttributes;
  var blockWrapperProps = useBlockWrapperProps();
  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Link settings')
  }, createElement(ToggleControl, {
    label: __('Open links in new tab'),
    checked: openInNewTab,
    onChange: function onChange() {
      return setAttributes({
        openInNewTab: !openInNewTab
      });
    }
  }))), createElement(InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS,
    templateLock: false,
    template: TEMPLATE,
    orientation: "horizontal",
    __experimentalTagName: "ul",
    __experimentalPassedProps: blockWrapperProps,
    __experimentalAppenderTagName: "li"
  }));
}
export default SocialLinksEdit;
//# sourceMappingURL=edit.js.map