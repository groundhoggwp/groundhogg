"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _keycodes = require("@wordpress/keycodes");

/**
 * WordPress dependencies
 */
var ColorSelectorSVGIcon = function ColorSelectorSVGIcon() {
  return (0, _element.createElement)(_components.SVG, {
    xmlns: "https://www.w3.org/2000/svg",
    viewBox: "0 0 20 20"
  }, (0, _element.createElement)(_components.Path, {
    d: "M7.434 5l3.18 9.16H8.538l-.692-2.184H4.628l-.705 2.184H2L5.18 5h2.254zm-1.13 1.904h-.115l-1.148 3.593H7.44L6.304 6.904zM14.348 7.006c1.853 0 2.9.876 2.9 2.374v4.78h-1.79v-.914h-.114c-.362.64-1.123 1.022-2.031 1.022-1.346 0-2.292-.826-2.292-2.108 0-1.27.972-2.006 2.71-2.107l1.696-.102V9.38c0-.584-.42-.914-1.18-.914-.667 0-1.112.228-1.264.647h-1.701c.12-1.295 1.307-2.107 3.066-2.107zm1.079 4.1l-1.416.09c-.793.056-1.18.342-1.18.844 0 .52.45.837 1.091.837.857 0 1.505-.545 1.505-1.256v-.515z"
  }));
};
/**
 * Color Selector Icon component.
 *
 * @param {Object} props           Component properties.
 * @param {Object} props.style     Style object.
 * @param {string} props.className Class name for component.
 *
 * @return {*} React Icon component.
 */


var ColorSelectorIcon = function ColorSelectorIcon(_ref) {
  var style = _ref.style,
      className = _ref.className;
  return (0, _element.createElement)("div", {
    className: "block-library-colors-selector__icon-container"
  }, (0, _element.createElement)("div", {
    className: "".concat(className, " block-library-colors-selector__state-selection"),
    style: style
  }, (0, _element.createElement)(ColorSelectorSVGIcon, null)));
};
/**
 * Renders the Colors Selector Toolbar with the icon button.
 *
 * @param {Object} props                 Component properties.
 * @param {Object} props.TextColor       Text color component that wraps icon.
 * @param {Object} props.BackgroundColor Background color component that wraps icon.
 *
 * @return {*} React toggle button component.
 */


var renderToggleComponent = function renderToggleComponent(_ref2) {
  var TextColor = _ref2.TextColor,
      BackgroundColor = _ref2.BackgroundColor;
  return function (_ref3) {
    var onToggle = _ref3.onToggle,
        isOpen = _ref3.isOpen;

    var openOnArrowDown = function openOnArrowDown(event) {
      if (!isOpen && event.keyCode === _keycodes.DOWN) {
        event.preventDefault();
        event.stopPropagation();
        onToggle();
      }
    };

    return (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
      className: "components-toolbar__control block-library-colors-selector__toggle",
      label: (0, _i18n.__)('Open Colors Selector'),
      onClick: onToggle,
      onKeyDown: openOnArrowDown,
      icon: (0, _element.createElement)(BackgroundColor, null, (0, _element.createElement)(TextColor, null, (0, _element.createElement)(ColorSelectorIcon, null)))
    }));
  };
};

var BlockColorsStyleSelector = function BlockColorsStyleSelector(_ref4) {
  var children = _ref4.children,
      other = (0, _objectWithoutProperties2.default)(_ref4, ["children"]);
  return (0, _element.createElement)(_components.Dropdown, {
    position: "bottom right",
    className: "block-library-colors-selector",
    contentClassName: "block-library-colors-selector__popover",
    renderToggle: renderToggleComponent(other),
    renderContent: function renderContent() {
      return children;
    }
  });
};

var _default = BlockColorsStyleSelector;
exports.default = _default;
//# sourceMappingURL=index.js.map