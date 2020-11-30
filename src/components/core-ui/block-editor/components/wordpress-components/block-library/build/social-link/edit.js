"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _socialList = require("./social-list");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var SocialLinkEdit = function SocialLinkEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      isSelected = _ref.isSelected;
  var url = attributes.url,
      service = attributes.service,
      label = attributes.label;

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      showURLPopover = _useState2[0],
      setPopover = _useState2[1];

  var classes = (0, _classnames.default)('wp-social-link', 'wp-social-link-' + service, {
    'wp-social-link__is-incomplete': !url
  });
  var IconComponent = (0, _socialList.getIconBySite)(service);
  var socialLinkName = (0, _socialList.getNameBySite)(service);
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: classes
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.sprintf)(
    /* translators: %s: name of the social service. */
    (0, _i18n.__)('%s label'), socialLinkName),
    initialOpen: false
  }, (0, _element.createElement)(_components.PanelRow, null, (0, _element.createElement)(_components.TextControl, {
    label: (0, _i18n.__)('Link label'),
    help: (0, _i18n.__)('Briefly describe the link to help screen reader users.'),
    value: label,
    onChange: function onChange(value) {
      return setAttributes({
        label: value
      });
    }
  })))), (0, _element.createElement)("li", blockWrapperProps, (0, _element.createElement)(_components.Button, {
    onClick: function onClick() {
      return setPopover(true);
    }
  }, (0, _element.createElement)(IconComponent, null), isSelected && showURLPopover && (0, _element.createElement)(_blockEditor.URLPopover, {
    onClose: function onClose() {
      return setPopover(false);
    }
  }, (0, _element.createElement)("form", {
    className: "block-editor-url-popover__link-editor",
    onSubmit: function onSubmit(event) {
      event.preventDefault();
      setPopover(false);
    }
  }, (0, _element.createElement)("div", {
    className: "block-editor-url-input"
  }, (0, _element.createElement)(_blockEditor.URLInput, {
    value: url,
    onChange: function onChange(nextURL) {
      return setAttributes({
        url: nextURL
      });
    },
    placeholder: (0, _i18n.__)('Enter address'),
    disableSuggestions: true
  })), (0, _element.createElement)(_components.Button, {
    icon: _icons.keyboardReturn,
    label: (0, _i18n.__)('Apply'),
    type: "submit"
  }))))));
};

var _default = SocialLinkEdit;
exports.default = _default;
//# sourceMappingURL=edit.js.map