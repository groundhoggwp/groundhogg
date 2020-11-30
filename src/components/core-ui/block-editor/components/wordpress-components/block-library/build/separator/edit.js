"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _separatorSettings = _interopRequireDefault(require("./separator-settings"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function SeparatorEdit(_ref) {
  var color = _ref.color,
      setColor = _ref.setColor,
      className = _ref.className;
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.HorizontalRule, {
    className: (0, _classnames2.default)(className, (0, _defineProperty2.default)({
      'has-background': color.color
    }, color.class, color.class)),
    style: {
      backgroundColor: color.color,
      color: color.color
    }
  }), (0, _element.createElement)(_separatorSettings.default, {
    color: color,
    setColor: setColor
  }));
}

var _default = (0, _blockEditor.withColors)('color', {
  textColor: 'color'
})(SeparatorEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map