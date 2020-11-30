"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function InserterSearchForm(_ref) {
  var className = _ref.className,
      _onChange = _ref.onChange,
      value = _ref.value,
      placeholder = _ref.placeholder;
  var instanceId = (0, _compose.useInstanceId)(InserterSearchForm);
  var searchInput = (0, _element.useRef)(); // Disable reason (no-autofocus): The inserter menu is a modal display, not one which
  // is always visible, and one which already incurs this behavior of autoFocus via
  // Popover's focusOnMount.

  /* eslint-disable jsx-a11y/no-autofocus */

  return (0, _element.createElement)("div", {
    className: (0, _classnames.default)('block-editor-inserter__search', className)
  }, (0, _element.createElement)(_components.VisuallyHidden, {
    as: "label",
    htmlFor: "block-editor-inserter__search-".concat(instanceId)
  }, placeholder), (0, _element.createElement)("input", {
    ref: searchInput,
    className: "block-editor-inserter__search-input",
    id: "block-editor-inserter__search-".concat(instanceId),
    type: "search",
    placeholder: placeholder,
    autoFocus: true,
    onChange: function onChange(event) {
      return _onChange(event.target.value);
    },
    autoComplete: "off",
    value: value || ''
  }), (0, _element.createElement)("div", {
    className: "block-editor-inserter__search-icon"
  }, !!value && (0, _element.createElement)(_components.Button, {
    icon: _icons.closeSmall,
    label: (0, _i18n.__)('Reset search'),
    onClick: function onClick() {
      _onChange('');

      searchInput.current.focus();
    }
  }), !value && (0, _element.createElement)(_icons.Icon, {
    icon: _icons.search
  })));
  /* eslint-enable jsx-a11y/no-autofocus */
}

var _default = InserterSearchForm;
exports.default = _default;
//# sourceMappingURL=search-form.js.map