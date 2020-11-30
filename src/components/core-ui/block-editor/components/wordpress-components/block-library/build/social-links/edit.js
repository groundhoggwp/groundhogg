"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.SocialLinksEdit = SocialLinksEdit;
exports.default = void 0;

var _element = require("@wordpress/element");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

/**
 * WordPress dependencies
 */
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

function SocialLinksEdit(props) {
  var openInNewTab = props.attributes.openInNewTab,
      setAttributes = props.setAttributes;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Link settings')
  }, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Open links in new tab'),
    checked: openInNewTab,
    onChange: function onChange() {
      return setAttributes({
        openInNewTab: !openInNewTab
      });
    }
  }))), (0, _element.createElement)(_blockEditor.InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS,
    templateLock: false,
    template: TEMPLATE,
    orientation: "horizontal",
    __experimentalTagName: "ul",
    __experimentalPassedProps: blockWrapperProps,
    __experimentalAppenderTagName: "li"
  }));
}

var _default = SocialLinksEdit;
exports.default = _default;
//# sourceMappingURL=edit.js.map