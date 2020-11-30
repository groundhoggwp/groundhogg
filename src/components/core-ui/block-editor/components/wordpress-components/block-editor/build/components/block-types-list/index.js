"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _reakit = require("reakit");

var _blocks = require("@wordpress/blocks");

var _inserterListItem = _interopRequireDefault(require("../inserter-list-item"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockTypesList(_ref) {
  var _ref$items = _ref.items,
      items = _ref$items === void 0 ? [] : _ref$items,
      onSelect = _ref.onSelect,
      _ref$onHover = _ref.onHover,
      onHover = _ref$onHover === void 0 ? function () {} : _ref$onHover,
      children = _ref.children,
      label = _ref.label;
  var composite = (0, _reakit.useCompositeState)();
  var orderId = items.reduce(function (acc, item) {
    return acc + '--' + item.id;
  }, ''); // This ensures the composite state refreshes when the list order changes.

  (0, _element.useEffect)(function () {
    composite.unstable_sort();
  }, [composite.unstable_sort, orderId]);
  return (
    /*
     * Disable reason: The `list` ARIA role is redundant but
     * Safari+VoiceOver won't announce the list otherwise.
     */

    /* eslint-disable jsx-a11y/no-redundant-roles */
    (0, _element.createElement)(_reakit.Composite, (0, _extends2.default)({}, composite, {
      role: "listbox",
      className: "block-editor-block-types-list",
      "aria-label": label
    }), items.map(function (item) {
      return (0, _element.createElement)(_inserterListItem.default, {
        key: item.id,
        className: (0, _blocks.getBlockMenuDefaultClassName)(item.id),
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

var _default = BlockTypesList;
exports.default = _default;
//# sourceMappingURL=index.js.map