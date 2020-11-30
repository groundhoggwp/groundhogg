import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Composite, useCompositeState } from 'reakit';
/**
 * WordPress dependencies
 */

import { getBlockMenuDefaultClassName } from '@wordpress/blocks';
import { useEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */

import InserterListItem from '../inserter-list-item';

function BlockTypesList(_ref) {
  var _ref$items = _ref.items,
      items = _ref$items === void 0 ? [] : _ref$items,
      onSelect = _ref.onSelect,
      _ref$onHover = _ref.onHover,
      onHover = _ref$onHover === void 0 ? function () {} : _ref$onHover,
      children = _ref.children,
      label = _ref.label;
  var composite = useCompositeState();
  var orderId = items.reduce(function (acc, item) {
    return acc + '--' + item.id;
  }, ''); // This ensures the composite state refreshes when the list order changes.

  useEffect(function () {
    composite.unstable_sort();
  }, [composite.unstable_sort, orderId]);
  return (
    /*
     * Disable reason: The `list` ARIA role is redundant but
     * Safari+VoiceOver won't announce the list otherwise.
     */

    /* eslint-disable jsx-a11y/no-redundant-roles */
    createElement(Composite, _extends({}, composite, {
      role: "listbox",
      className: "block-editor-block-types-list",
      "aria-label": label
    }), items.map(function (item) {
      return createElement(InserterListItem, {
        key: item.id,
        className: getBlockMenuDefaultClassName(item.id),
        icon: item.icon,
        onClick: function onClick() {
          onSelect(item);
          onHover(null);
        },
        onFocus: function onFocus() {
          return onHover(item);
        },
        onMouseEnter: function onMouseEnter() {
          return onHover(item);
        },
        onMouseLeave: function onMouseLeave() {
          return onHover(null);
        },
        onBlur: function onBlur() {
          return onHover(null);
        },
        isDisabled: item.isDisabled,
        title: item.title,
        composite: composite
      });
    }), children)
    /* eslint-enable jsx-a11y/no-redundant-roles */

  );
}

export default BlockTypesList;
//# sourceMappingURL=index.js.map