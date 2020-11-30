import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __experimentalUseBlockWrapperProps as useBlockWrapperProps, BlockControls, InspectorControls, RichText, __experimentalUnitControl as UnitControl } from '@wordpress/block-editor';
import { DropdownMenu, MenuGroup, MenuItem, ToolbarGroup, Button, ButtonGroup, ToolbarButton, ResizableBox, PanelBody, BaseControl } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { search } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import { buttonOnly, buttonOutside, buttonInside, noButton, buttonWithIcon, toggleLabel } from './icons';
/**
 * Constants
 */

var MIN_WIDTH = 220;
var MIN_WIDTH_UNIT = 'px';
var PC_WIDTH_DEFAULT = 50;
var PX_WIDTH_DEFAULT = 350;
var CSS_UNITS = [{
  value: '%',
  label: '%',
  default: PC_WIDTH_DEFAULT
}, {
  value: 'px',
  label: 'px',
  default: PX_WIDTH_DEFAULT
}];
export default function SearchEdit(_ref) {
  var className = _ref.className,
      attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      toggleSelection = _ref.toggleSelection,
      isSelected = _ref.isSelected;
  var label = attributes.label,
      showLabel = attributes.showLabel,
      placeholder = attributes.placeholder,
      width = attributes.width,
      widthUnit = attributes.widthUnit,
      align = attributes.align,
      buttonText = attributes.buttonText,
      buttonPosition = attributes.buttonPosition,
      buttonUseIcon = attributes.buttonUseIcon;
  var unitControlInstanceId = useInstanceId(UnitControl);
  var unitControlInputId = "wp-block-search__width-".concat(unitControlInstanceId);

  var getBlockClassNames = function getBlockClassNames() {
    return classnames(className, 'button-inside' === buttonPosition ? 'wp-block-search__button-inside' : undefined, 'button-outside' === buttonPosition ? 'wp-block-search__button-outside' : undefined, 'no-button' === buttonPosition ? 'wp-block-search__no-button' : undefined, 'button-only' === buttonPosition ? 'wp-block-search__button-only' : undefined, buttonUseIcon && 'no-button' !== buttonPosition ? 'wp-block-search__text-button' : undefined, !buttonUseIcon && 'no-button' !== buttonPosition ? 'wp-block-search__icon-button' : undefined);
  };

  var getButtonPositionIcon = function getButtonPositionIcon() {
    switch (buttonPosition) {
      case 'button-inside':
        return buttonInside;

      case 'button-outside':
        return buttonOutside;

      case 'no-button':
        return noButton;

      case 'button-only':
        return buttonOnly;
    }
  };

  var getResizableSides = function getResizableSides() {
    if ('button-only' === buttonPosition) {
      return {};
    }

    return {
      right: align === 'right' ? false : true,
      left: align === 'right' ? true : false
    };
  };

  var renderTextField = function renderTextField() {
    return createElement("input", {
      className: "wp-block-search__input",
      "aria-label": __('Optional placeholder text') // We hide the placeholder field's placeholder when there is a value. This
      // stops screen readers from reading the placeholder field's placeholder
      // which is confusing.
      ,
      placeholder: placeholder ? undefined : __('Optional placeholder…'),
      value: placeholder,
      onChange: function onChange(event) {
        return setAttributes({
          placeholder: event.target.value
        });
      }
    });
  };

  var renderButton = function renderButton() {
    return createElement(Fragment, null, buttonUseIcon && createElement(Button, {
      icon: search,
      className: "wp-block-search__button"
    }), !buttonUseIcon && createElement(RichText, {
      className: "wp-block-search__button",
      "aria-label": __('Button text'),
      placeholder: __('Add button text…'),
      withoutInteractiveFormatting: true,
      value: buttonText,
      onChange: function onChange(html) {
        return setAttributes({
          buttonText: html
        });
      }
    }));
  };

  var controls = createElement(Fragment, null, createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(ToolbarButton, {
    title: __('Toggle search label'),
    icon: toggleLabel,
    onClick: function onClick() {
      setAttributes({
        showLabel: !showLabel
      });
    },
    className: showLabel ? 'is-pressed' : undefined
  }), createElement(DropdownMenu, {
    icon: getButtonPositionIcon(),
    label: __('Change button position')
  }, function (_ref2) {
    var onClose = _ref2.onClose;
    return createElement(MenuGroup, {
      className: "wp-block-search__button-position-menu"
    }, createElement(MenuItem, {
      icon: noButton,
      onClick: function onClick() {
        setAttributes({
          buttonPosition: 'no-button'
        });
        onClose();
      }
    }, __('No Button')), createElement(MenuItem, {
      icon: buttonOutside,
      onClick: function onClick() {
        setAttributes({
          buttonPosition: 'button-outside'
        });
        onClose();
      }
    }, __('Button Outside')), createElement(MenuItem, {
      icon: buttonInside,
      onClick: function onClick() {
        setAttributes({
          buttonPosition: 'button-inside'
        });
        onClose();
      }
    }, __('Button Inside')), createElement(MenuItem, {
      icon: buttonOnly,
      onClick: function onClick() {
        setAttributes({
          buttonPosition: 'button-only'
        });
        onClose();
      }
    }, __('Button Only')));
  }), 'no-button' !== buttonPosition && createElement(ToolbarButton, {
    title: __('Use button with icon'),
    icon: buttonWithIcon,
    onClick: function onClick() {
      setAttributes({
        buttonUseIcon: !buttonUseIcon
      });
    },
    className: buttonUseIcon ? 'is-pressed' : undefined
  }))), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Display Settings')
  }, createElement(BaseControl, {
    label: __('Width'),
    id: unitControlInputId
  }, createElement(UnitControl, {
    id: unitControlInputId,
    min: "".concat(MIN_WIDTH).concat(MIN_WIDTH_UNIT),
    onChange: function onChange(newWidth) {
      var filteredWidth = widthUnit === '%' && parseInt(newWidth, 10) > 100 ? 100 : newWidth;
      setAttributes({
        width: parseInt(filteredWidth, 10)
      });
    },
    onUnitChange: function onUnitChange(newUnit) {
      setAttributes({
        width: '%' === newUnit ? PC_WIDTH_DEFAULT : PX_WIDTH_DEFAULT,
        widthUnit: newUnit
      });
    },
    style: {
      maxWidth: 80
    },
    value: "".concat(width).concat(widthUnit),
    unit: widthUnit,
    units: CSS_UNITS
  }), createElement(ButtonGroup, {
    className: "wp-block-search__components-button-group",
    "aria-label": __('Percentage Width')
  }, [25, 50, 75, 100].map(function (widthValue) {
    return createElement(Button, {
      key: widthValue,
      isSmall: true,
      isPrimary: "".concat(widthValue, "%") === "".concat(width).concat(widthUnit),
      onClick: function onClick() {
        return setAttributes({
          width: widthValue,
          widthUnit: '%'
        });
      }
    }, widthValue, "%");
  }))))));
  var blockWrapperProps = useBlockWrapperProps({
    className: getBlockClassNames()
  });
  return createElement("div", blockWrapperProps, controls, showLabel && createElement(RichText, {
    className: "wp-block-search__label",
    "aria-label": __('Label text'),
    placeholder: __('Add label…'),
    withoutInteractiveFormatting: true,
    value: label,
    onChange: function onChange(html) {
      return setAttributes({
        label: html
      });
    }
  }), createElement(ResizableBox, {
    size: {
      width: "".concat(width).concat(widthUnit)
    },
    className: "wp-block-search__inside-wrapper",
    isResetValueOnUnitChange: true,
    minWidth: MIN_WIDTH,
    enable: getResizableSides(),
    onResizeStart: function onResizeStart(event, direction, elt) {
      setAttributes({
        width: parseInt(elt.offsetWidth, 10),
        widthUnit: 'px'
      });
      toggleSelection(false);
    },
    onResizeStop: function onResizeStop(event, direction, elt, delta) {
      setAttributes({
        width: parseInt(width + delta.width, 10)
      });
      toggleSelection(true);
    },
    showHandle: isSelected
  }, ('button-inside' === buttonPosition || 'button-outside' === buttonPosition) && createElement(Fragment, null, renderTextField(), renderButton()), 'button-only' === buttonPosition && renderButton(), 'no-button' === buttonPosition && renderTextField()));
}
//# sourceMappingURL=edit.js.map