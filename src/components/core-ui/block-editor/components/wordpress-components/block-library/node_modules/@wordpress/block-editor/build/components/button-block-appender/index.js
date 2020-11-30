"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _inserter = _interopRequireDefault(require("../inserter"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ButtonBlockAppender(_ref, ref) {
  var rootClientId = _ref.rootClientId,
      className = _ref.className,
      selectBlockOnInsert = _ref.__experimentalSelectBlockOnInsert,
      onFocus = _ref.onFocus,
      tabIndex = _ref.tabIndex;
  return (0, _element.createElement)(_inserter.default, {
    position: "bottom center",
    rootClientId: rootClientId,
    __experimentalSelectBlockOnInsert: selectBlockOnInsert,
    __experimentalIsQuick: true,
    renderToggle: function renderToggle(_ref2) {
      var onToggle = _ref2.onToggle,
          disabled = _ref2.disabled,
          isOpen = _ref2.isOpen,
          blockTitle = _ref2.blockTitle,
          hasSingleBlockType = _ref2.hasSingleBlockType;
      var label;

      if (hasSingleBlockType) {
        label = (0, _i18n.sprintf)( // translators: %s: the name of the block when there is only one
        (0, _i18n._x)('Add %s', 'directly add the only allowed block'), blockTitle);
      } else {
        label = (0, _i18n._x)('Add block', 'Generic label for block inserter button');
      }

      var isToggleButton = !hasSingleBlockType;
      var inserterButton = (0, _element.createElement)(_components.Button, {
        ref: ref,
        onFocus: onFocus,
        tabIndex: tabIndex,
        className: (0, _classnames.default)(className, 'block-editor-button-block-appender'),
        onClick: onToggle,
        "aria-haspopup": isToggleButton ? 'true' : undefined,
        "aria-expanded": isToggleButton ? isOpen : undefined,
        disabled: disabled,
        label: label
      }, !hasSingleBlockType && (0, _element.createElement)(_components.VisuallyHidden, {
        as: "span"
      }, label), (0, _element.createElement)(_icons.Icon, {
        icon: _icons.plus
      }));

      if (isToggleButton || hasSingleBlockType) {
        inserterButton = (0, _element.createElement)(_components.Tooltip, {
          text: label
        }, inserterButton);
      }

      return inserterButton;
    },
    isAppender: true
  });
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/button-block-appender/README.md
 */


var _default = (0, _element.forwardRef)(ButtonBlockAppender);

exports.default = _default;
//# sourceMappingURL=index.js.map