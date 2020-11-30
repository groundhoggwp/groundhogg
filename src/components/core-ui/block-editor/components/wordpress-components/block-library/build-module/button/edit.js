import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';
import { KeyboardShortcuts, PanelBody, RangeControl, TextControl, ToggleControl, ToolbarButton, ToolbarGroup, Popover } from '@wordpress/components';
import { BlockControls, InspectorControls, RichText, __experimentalUseBlockWrapperProps as useBlockWrapperProps, __experimentalLinkControl as LinkControl, __experimentalUseEditorFeature as useEditorFeature } from '@wordpress/block-editor';
import { rawShortcut, displayShortcut } from '@wordpress/keycodes';
import { link, linkOff } from '@wordpress/icons';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import ColorEdit from './color-edit';
import getColorAndStyleProps from './color-props';
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
  var setBorderRadius = useCallback(function (newBorderRadius) {
    if (newBorderRadius === undefined) setAttributes({
      borderRadius: initialBorderRadius
    });else setAttributes({
      borderRadius: newBorderRadius
    });
  }, [setAttributes]);
  return createElement(PanelBody, {
    title: __('Border settings')
  }, createElement(RangeControl, {
    value: borderRadius,
    label: __('Border radius'),
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

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
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

  var linkControl = (isURLPickerOpen || urlIsSetandSelected) && createElement(Popover, {
    position: "bottom center",
    onClose: function onClose() {
      return setIsURLPickerOpen(false);
    }
  }, createElement(LinkControl, {
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
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(ToolbarGroup, null, !urlIsSet && createElement(ToolbarButton, {
    name: "link",
    icon: link,
    title: __('Link'),
    shortcut: displayShortcut.primary('k'),
    onClick: openLinkControl
  }), urlIsSetandSelected && createElement(ToolbarButton, {
    name: "link",
    icon: linkOff,
    title: __('Unlink'),
    shortcut: displayShortcut.primaryShift('k'),
    onClick: unlinkButton,
    isActive: true
  }))), isSelected && createElement(KeyboardShortcuts, {
    bindGlobal: true,
    shortcuts: (_ref4 = {}, _defineProperty(_ref4, rawShortcut.primary('k'), openLinkControl), _defineProperty(_ref4, rawShortcut.primaryShift('k'), unlinkButton), _ref4)
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
  var onSetLinkRel = useCallback(function (value) {
    setAttributes({
      rel: value
    });
  }, [setAttributes]);
  var colors = useEditorFeature('color.palette') || EMPTY_ARRAY;
  var onToggleOpenInNewTab = useCallback(function (value) {
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
  var colorProps = getColorAndStyleProps(attributes, colors, true);
  var blockWrapperProps = useBlockWrapperProps();
  return createElement(Fragment, null, createElement(ColorEdit, props), createElement("div", blockWrapperProps, createElement(RichText, {
    placeholder: placeholder || __('Add text…'),
    value: text,
    onChange: function onChange(value) {
      return setAttributes({
        text: value
      });
    },
    withoutInteractiveFormatting: true,
    className: classnames(className, 'wp-block-button__link', colorProps.className, {
      'no-border-radius': borderRadius === 0
    }),
    style: _objectSpread({
      borderRadius: borderRadius ? borderRadius + 'px' : undefined
    }, colorProps.style),
    onSplit: function onSplit(value) {
      return createBlock('core/button', _objectSpread(_objectSpread({}, attributes), {}, {
        text: value
      }));
    },
    onReplace: onReplace,
    onMerge: mergeBlocks,
    identifier: "text"
  })), createElement(URLPicker, {
    url: url,
    setAttributes: setAttributes,
    isSelected: isSelected,
    opensInNewTab: linkTarget === '_blank',
    onToggleOpenInNewTab: onToggleOpenInNewTab
  }), createElement(InspectorControls, null, createElement(BorderPanel, {
    borderRadius: borderRadius,
    setAttributes: setAttributes
  }), createElement(PanelBody, {
    title: __('Link settings')
  }, createElement(ToggleControl, {
    label: __('Open in new tab'),
    onChange: onToggleOpenInNewTab,
    checked: linkTarget === '_blank'
  }), createElement(TextControl, {
    label: __('Link rel'),
    value: rel || '',
    onChange: onSetLinkRel
  }))));
}

export default ButtonEdit;
//# sourceMappingURL=edit.js.map