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

var _classnames = _interopRequireDefault(require("classnames"));

var _compose = require("@wordpress/compose");

var _icon = _interopRequireDefault(require("../icon"));

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
 * Renders a placeholder. Normally used by blocks to render their empty state.
 *
 * @param {Object}    props                The component props.
 * @param {WPIcon}    props.icon           An icon rendered before the label.
 * @param {WPElement} props.children       Children to be rendered.
 * @param {string}    props.label          Title of the placeholder.
 * @param {string}    props.instructions   Instructions of the placeholder.
 * @param {string}    props.className      Class to set on the container div.
 * @param {Object}    props.notices        A rendered notices list.
 * @param {Object}    props.preview        Preview to be rendered in the placeholder.
 * @param {boolean}   props.isColumnLayout Whether a column layout should be used.
 *
 * @return {Object}       The rendered placeholder.
 */
function Placeholder(_ref) {
  var icon = _ref.icon,
      children = _ref.children,
      label = _ref.label,
      instructions = _ref.instructions,
      className = _ref.className,
      notices = _ref.notices,
      preview = _ref.preview,
      isColumnLayout = _ref.isColumnLayout,
      additionalProps = (0, _objectWithoutProperties2.default)(_ref, ["icon", "children", "label", "instructions", "className", "notices", "preview", "isColumnLayout"]);

  var _useResizeObserver = (0, _compose.useResizeObserver)(),
      _useResizeObserver2 = (0, _slicedToArray2.default)(_useResizeObserver, 2),
      resizeListener = _useResizeObserver2[0],
      width = _useResizeObserver2[1].width; // Since `useResizeObserver` will report a width of `null` until after the
  // first render, avoid applying any modifier classes until width is known.


  var modifierClassNames;

  if (typeof width === 'number') {
    modifierClassNames = {
      'is-large': width >= 320,
      'is-medium': width >= 160 && width < 320,
      'is-small': width < 160
    };
  }

  var classes = (0, _classnames.default)('components-placeholder', className, modifierClassNames);
  var fieldsetClasses = (0, _classnames.default)('components-placeholder__fieldset', {
    'is-column-layout': isColumnLayout
  });
  return (0, _element.createElement)("div", (0, _extends2.default)({}, additionalProps, {
    className: classes
  }), resizeListener, notices, preview && (0, _element.createElement)("div", {
    className: "components-placeholder__preview"
  }, preview), (0, _element.createElement)("div", {
    className: "components-placeholder__label"
  }, (0, _element.createElement)(_icon.default, {
    icon: icon
  }), label), !!instructions && (0, _element.createElement)("div", {
    className: "components-placeholder__instructions"
  }, instructions), (0, _element.createElement)("div", {
    className: fieldsetClasses
  }, children));
}

var _default = Placeholder;
exports.default = _default;
//# sourceMappingURL=index.js.map