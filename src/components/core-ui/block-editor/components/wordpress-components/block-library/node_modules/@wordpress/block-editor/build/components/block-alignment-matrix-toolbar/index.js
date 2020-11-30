"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockAlignmentMatrixToolbar = BlockAlignmentMatrixToolbar;
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _keycodes = require("@wordpress/keycodes");

var _components = require("@wordpress/components");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function BlockAlignmentMatrixToolbar(props) {
  var _props$label = props.label,
      label = _props$label === void 0 ? (0, _i18n.__)('Change matrix alignment') : _props$label,
      _props$onChange = props.onChange,
      onChange = _props$onChange === void 0 ? _lodash.noop : _props$onChange,
      _props$value = props.value,
      value = _props$value === void 0 ? 'center' : _props$value;
  var icon = (0, _element.createElement)(_components.__experimentalAlignmentMatrixControl.Icon, {
    value: value
  });
  var className = 'block-editor-block-alignment-matrix-toolbar';
  var popoverClassName = "".concat(className, "__popover");
  var isAlternate = true;
  return (0, _element.createElement)(_components.Dropdown, {
    position: "bottom right",
    className: className,
    popoverProps: {
      className: popoverClassName,
      isAlternate: isAlternate
    },
    renderToggle: function renderToggle(_ref) {
      var onToggle = _ref.onToggle,
          isOpen = _ref.isOpen;

      var openOnArrowDown = function openOnArrowDown(event) {
        if (!isOpen && event.keyCode === _keycodes.DOWN) {
          event.preventDefault();
          event.stopPropagation();
          onToggle();
        }
      };

      return (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
        onClick: onToggle,
        "aria-haspopup": "true",
        "aria-expanded": isOpen,
        onKeyDown: openOnArrowDown,
        label: label,
        icon: icon,
        showTooltip: true
      }));
    },
    renderContent: function renderContent() {
      return (0, _element.createElement)(_components.__experimentalAlignmentMatrixControl, {
        hasFocusBorder: false,
        onChange: onChange,
        value: value
      });
    }
  });
}

var _default = BlockAlignmentMatrixToolbar;
exports.default = _default;
//# sourceMappingURL=index.js.map