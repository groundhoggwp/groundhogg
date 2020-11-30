import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { orderBy } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { ToolbarItem, ToolbarGroup, DropdownMenu, Slot } from '@wordpress/components';
import { chevronDown } from '@wordpress/icons';
var POPOVER_PROPS = {
  position: 'bottom right',
  isAlternate: true
};

var FormatToolbar = function FormatToolbar() {
  return createElement("div", {
    className: "block-editor-format-toolbar"
  }, createElement(ToolbarGroup, null, ['bold', 'italic', 'link', 'text-color'].map(function (format) {
    return createElement(Slot, {
      name: "RichText.ToolbarControls.".concat(format),
      key: format
    });
  }), createElement(Slot, {
    name: "RichText.ToolbarControls"
  }, function (fills) {
    return fills.length !== 0 && createElement(ToolbarItem, null, function (toggleProps) {
      return createElement(DropdownMenu, {
        icon: chevronDown,
        label: __('More rich text controls'),
        toggleProps: toggleProps,
        controls: orderBy(fills.map(function (_ref) {
          var _ref2 = _slicedToArray(_ref, 1),
              props = _ref2[0].props;

          return props;
        }), 'title'),
        popoverProps: POPOVER_PROPS
      });
    });
  })));
};

export default FormatToolbar;
//# sourceMappingURL=index.js.map