import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { DOWN } from '@wordpress/keycodes';
import { ToolbarButton, Dropdown, ToolbarGroup, __experimentalAlignmentMatrixControl as AlignmentMatrixControl } from '@wordpress/components';
export function BlockAlignmentMatrixToolbar(props) {
  var _props$label = props.label,
      label = _props$label === void 0 ? __('Change matrix alignment') : _props$label,
      _props$onChange = props.onChange,
      onChange = _props$onChange === void 0 ? noop : _props$onChange,
      _props$value = props.value,
      value = _props$value === void 0 ? 'center' : _props$value;
  var icon = createElement(AlignmentMatrixControl.Icon, {
    value: value
  });
  var className = 'block-editor-block-alignment-matrix-toolbar';
  var popoverClassName = "".concat(className, "__popover");
  var isAlternate = true;
  return createElement(Dropdown, {
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
        if (!isOpen && event.keyCode === DOWN) {
          event.preventDefault();
          event.stopPropagation();
          onToggle();
        }
      };

      return createElement(ToolbarGroup, null, createElement(ToolbarButton, {
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
      return createElement(AlignmentMatrixControl, {
        hasFocusBorder: false,
        onChange: onChange,
        value: value
      });
    }
  });
}
export default BlockAlignmentMatrixToolbar;
//# sourceMappingURL=index.js.map