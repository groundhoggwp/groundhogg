"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.addGeneratedClassName = addGeneratedClassName;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _hooks = require("@wordpress/hooks");

var _blocks = require("@wordpress/blocks");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Override props assigned to save component to inject generated className if
 * block supports it. This is only applied if the block's save result is an
 * element and not a markup string.
 *
 * @param {Object} extraProps Additional props applied to save element.
 * @param {Object} blockType  Block type.
 *
 * @return {Object} Filtered props applied to save element.
 */
function addGeneratedClassName(extraProps, blockType) {
  // Adding the generated className
  if ((0, _blocks.hasBlockSupport)(blockType, 'className', true)) {
    if (typeof extraProps.className === 'string') {
      // We have some extra classes and want to add the default classname
      // We use uniq to prevent duplicate classnames
      extraProps.className = (0, _lodash.uniq)([(0, _blocks.getBlockDefaultClassName)(blockType.name)].concat((0, _toConsumableArray2.default)(extraProps.className.split(' ')))).join(' ').trim();
    } else {
      // There is no string in the className variable,
      // so we just dump the default name in there
      extraProps.className = (0, _blocks.getBlockDefaultClassName)(blockType.name);
    }
  }

  return extraProps;
}

(0, _hooks.addFilter)('blocks.getSaveContent.extraProps', 'core/generated-class-name/save-props', addGeneratedClassName);
//# sourceMappingURL=generated-class-name.js.map