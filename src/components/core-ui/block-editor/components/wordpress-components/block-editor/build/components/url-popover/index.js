"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _linkViewer = _interopRequireDefault(require("./link-viewer"));

var _linkEditor = _interopRequireDefault(require("./link-editor"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function URLPopover(_ref) {
  var additionalControls = _ref.additionalControls,
      children = _ref.children,
      renderSettings = _ref.renderSettings,
      _ref$position = _ref.position,
      position = _ref$position === void 0 ? 'bottom center' : _ref$position,
      _ref$focusOnMount = _ref.focusOnMount,
      focusOnMount = _ref$focusOnMount === void 0 ? 'firstElement' : _ref$focusOnMount,
      popoverProps = (0, _objectWithoutProperties2.default)(_ref, ["additionalControls", "children", "renderSettings", "position", "focusOnMount"]);

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isSettingsExpanded = _useState2[0],
      setIsSettingsExpanded = _useState2[1];

  var showSettings = !!renderSettings && isSettingsExpanded;

  var toggleSettingsVisibility = function toggleSettingsVisibility() {
    setIsSettingsExpanded(!isSettingsExpanded);
  };

  return (0, _element.createElement)(_components.Popover, (0, _extends2.default)({
    className: "block-editor-url-popover",
    focusOnMount: focusOnMount,
    position: position
  }, popoverProps), (0, _element.createElement)("div", {
    className: "block-editor-url-popover__input-container"
  }, (0, _element.createElement)("div", {
    className: "block-editor-url-popover__row"
  }, children, !!renderSettings && (0, _element.createElement)(_components.Button, {
    className: "block-editor-url-popover__settings-toggle",
    icon: _icons.chevronDown,
    label: (0, _i18n.__)('Link settings'),
    onClick: toggleSettingsVisibility,
    "aria-expanded": isSettingsExpanded
  })), showSettings && (0, _element.createElement)("div", {
    className: "block-editor-url-popover__row block-editor-url-popover__settings"
  }, renderSettings())), additionalControls && !showSettings && (0, _element.createElement)("div", {
    className: "block-editor-url-popover__additional-controls"
  }, additionalControls));
}

URLPopover.LinkEditor = _linkEditor.default;
URLPopover.LinkViewer = _linkViewer.default;
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/url-popover/README.md
 */

var _default = URLPopover;
exports.default = _default;
//# sourceMappingURL=index.js.map