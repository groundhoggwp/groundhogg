import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classNames from 'classnames';
/**
 * WordPress dependencies
 */

import { InspectorControls, URLPopover, URLInput, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { Fragment, useState } from '@wordpress/element';
import { Button, PanelBody, PanelRow, TextControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { keyboardReturn } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import { getIconBySite, getNameBySite } from './social-list';

var SocialLinkEdit = function SocialLinkEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      isSelected = _ref.isSelected;
  var url = attributes.url,
      service = attributes.service,
      label = attributes.label;

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      showURLPopover = _useState2[0],
      setPopover = _useState2[1];

  var classes = classNames('wp-social-link', 'wp-social-link-' + service, {
    'wp-social-link__is-incomplete': !url
  });
  var IconComponent = getIconBySite(service);
  var socialLinkName = getNameBySite(service);
  var blockWrapperProps = useBlockWrapperProps({
    className: classes
  });
  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: sprintf(
    /* translators: %s: name of the social service. */
    __('%s label'), socialLinkName),
    initialOpen: false
  }, createElement(PanelRow, null, createElement(TextControl, {
    label: __('Link label'),
    help: __('Briefly describe the link to help screen reader users.'),
    value: label,
    onChange: function onChange(value) {
      return setAttributes({
        label: value
      });
    }
  })))), createElement("li", blockWrapperProps, createElement(Button, {
    onClick: function onClick() {
      return setPopover(true);
    }
  }, createElement(IconComponent, null), isSelected && showURLPopover && createElement(URLPopover, {
    onClose: function onClose() {
      return setPopover(false);
    }
  }, createElement("form", {
    className: "block-editor-url-popover__link-editor",
    onSubmit: function onSubmit(event) {
      event.preventDefault();
      setPopover(false);
    }
  }, createElement("div", {
    className: "block-editor-url-input"
  }, createElement(URLInput, {
    value: url,
    onChange: function onChange(nextURL) {
      return setAttributes({
        url: nextURL
      });
    },
    placeholder: __('Enter address'),
    disableSuggestions: true
  })), createElement(Button, {
    icon: keyboardReturn,
    label: __('Apply'),
    type: "submit"
  }))))));
};

export default SocialLinkEdit;
//# sourceMappingURL=edit.js.map