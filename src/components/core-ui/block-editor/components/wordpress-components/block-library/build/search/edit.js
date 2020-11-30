"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = SearchEdit;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

var _i18n = require("@wordpress/i18n");

var _icons2 = require("./icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

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

function SearchEdit(_ref) {
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
  var unitControlInstanceId = (0, _compose.useInstanceId)(_blockEditor.__experimentalUnitControl);
  var unitControlInputId = "wp-block-search__width-".concat(unitControlInstanceId);

  var getBlockClassNames = function getBlockClassNames() {
    return (0, _classnames.default)(className, 'button-inside' === buttonPosition ? 'wp-block-search__button-inside' : undefined, 'button-outside' === buttonPosition ? 'wp-block-search__button-outside' : undefined, 'no-button' === buttonPosition ? 'wp-block-search__no-button' : undefined, 'button-only' === buttonPosition ? 'wp-block-search__button-only' : undefined, buttonUseIcon && 'no-button' !== buttonPosition ? 'wp-block-search__text-button' : undefined, !buttonUseIcon && 'no-button' !== buttonPosition ? 'wp-block-search__icon-button' : undefined);
  };

  var getButtonPositionIcon = function getButtonPositionIcon() {
    switch (buttonPosition) {
      case 'button-inside':
        return _icons2.buttonInside;

      case 'button-outside':
        return _icons2.buttonOutside;

      case 'no-button':
        return _icons2.noButton;

      case 'button-only':
        return _icons2.buttonOnly;
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
    return (0, _element.createElement)("input", {
      className: "wp-block-search__input",
      "aria-label": (0, _i18n.__)('Optional placeholder text') // We hide the placeholder field's placeholder when there is a value. This
      // stops screen readers from reading the placeholder field's placeholder
      // which is confusing.
      ,
      placeholder: placeholder ? undefined : (0, _i18n.__)('Optional placeholder…'),
      value: placeholder,
      onChange: function onChange(event) {
        return setAttributes({
          placeholder: event.target.value
        });
      }
    });
  };

  var renderButton = function renderButton() {
    return (0, _element.createElement)(_element.Fragment, null, buttonUseIcon && (0, _element.createElement)(_components.Button, {
      icon: _icons.search,
      className: "wp-block-search__button"
    }), !buttonUseIcon && (0, _element.createElement)(_blockEditor.RichText, {
      className: "wp-block-search__button",
      "aria-label": (0, _i18n.__)('Button text'),
      placeholder: (0, _i18n.__)('Add button text…'),
      withoutInteractiveFormatting: true,
      value: buttonText,
      onChange: function onChange(html) {
        return setAttributes({
          buttonText: html
        });
      }
    }));
  };

  var controls = (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
    title: (0, _i18n.__)('Toggle search label'),
    icon: _icons2.toggleLabel,
    onClick: function onClick() {
      setAttributes({
        showLabel: !showLabel
      });
    },
    className: showLabel ? 'is-pressed' : undefined
  }), (0, _element.createElement)(_components.DropdownMenu, {
    icon: getButtonPositionIcon(),
    label: (0, _i18n.__)('Change button position')
  }, function (_ref2) {
    var onClose = _ref2.onClose;
    return (0, _element.createElement)(_components.MenuGroup, {
      className: "wp-block-search__button-position-menu"
    }, (0, _element.createElement)(_components.MenuItem, {
      icon: _icons2.noButton,
      onClick: function onClick() {
        setAttributes({
          buttonPosition: 'no-button'
        });
        onClose();
      }
    }, (0, _i18n.__)('No Button')), (0, _element.createElement)(_components.MenuItem, {
      icon: _icons2.buttonOutside,
      onClick: function onClick() {
        setAttributes({
          buttonPosition: 'button-outside'
        });
        onClose();
      }
    }, (0, _i18n.__)('Button Outside')), (0, _element.createElement)(_components.MenuItem, {
      icon: _icons2.buttonInside,
      onClick: function onClick() {
        setAttributes({
          buttonPosition: 'button-inside'
        });
        onClose();
      }
    }, (0, _i18n.__)('Button Inside')), (0, _element.createElement)(_components.MenuItem, {
      icon: _icons2.buttonOnly,
      onClick: function onClick() {
        setAttributes({
          buttonPosition: 'button-only'
        });
        onClose();
      }
    }, (0, _i18n.__)('Button Only')));
  }), 'no-button' !== buttonPosition && (0, _element.createElement)(_components.ToolbarButton, {
    title: (0, _i18n.__)('Use button with icon'),
    icon: _icons2.buttonWithIcon,
    onClick: function onClick() {
      setAttributes({
        buttonUseIcon: !buttonUseIcon
      });
    },
    className: buttonUseIcon ? 'is-pressed' : undefined
  }))), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Display Settings')
  }, (0, _element.createElement)(_components.BaseControl, {
    label: (0, _i18n.__)('Width'),
    id: unitControlInputId
  }, (0, _element.createElement)(_blockEditor.__experimentalUnitControl, {
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
  }), (0, _element.createElement)(_components.ButtonGroup, {
    className: "wp-block-search__components-button-group",
    "aria-label": (0, _i18n.__)('Percentage Width')
  }, [25, 50, 75, 100].map(function (widthValue) {
    return (0, _element.createElement)(_components.Button, {
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
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: getBlockClassNames()
  });
  return (0, _element.createElement)("div", blockWrapperProps, controls, showLabel && (0, _element.createElement)(_blockEditor.RichText, {
    className: "wp-block-search__label",
    "aria-label": (0, _i18n.__)('Label text'),
    placeholder: (0, _i18n.__)('Add label…'),
    withoutInteractiveFormatting: true,
    value: label,
    onChange: function onChange(html) {
      return setAttributes({
        label: html
      });
    }
  }), (0, _element.createElement)(_components.ResizableBox, {
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
  }, ('button-inside' === buttonPosition || 'button-outside' === buttonPosition) && (0, _element.createElement)(_element.Fragment, null, renderTextField(), renderButton()), 'button-only' === buttonPosition && renderButton(), 'no-button' === buttonPosition && renderTextField()));
}
//# sourceMappingURL=edit.js.map