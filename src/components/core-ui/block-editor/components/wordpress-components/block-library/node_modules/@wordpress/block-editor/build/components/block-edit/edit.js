"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.Edit = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

var _blockContext = _interopRequireDefault(require("../block-context"));

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
 * Default value used for blocks which do not define their own context needs,
 * used to guarantee that a block's `context` prop will always be an object. It
 * is assigned as a constant since it is always expected to be an empty object,
 * and in order to avoid unnecessary React reconciliations of a changing object.
 *
 * @type {{}}
 */
var DEFAULT_BLOCK_CONTEXT = {};

var Edit = function Edit(props) {
  var _props$attributes = props.attributes,
      attributes = _props$attributes === void 0 ? {} : _props$attributes,
      name = props.name;
  var blockType = (0, _blocks.getBlockType)(name);
  var blockContext = (0, _element.useContext)(_blockContext.default); // Assign context values using the block type's declared context needs.

  var context = (0, _element.useMemo)(function () {
    return blockType && blockType.usesContext ? (0, _lodash.pick)(blockContext, blockType.usesContext) : DEFAULT_BLOCK_CONTEXT;
  }, [blockType, blockContext]);

  if (!blockType) {
    return null;
  } // `edit` and `save` are functions or components describing the markup
  // with which a block is displayed. If `blockType` is valid, assign
  // them preferentially as the render value for the block.


  var Component = blockType.edit || blockType.save;
  var lightBlockWrapper = (0, _blocks.hasBlockSupport)(blockType, 'lightBlockWrapper', false);

  if (lightBlockWrapper) {
    return (0, _element.createElement)(Component, (0, _extends2.default)({}, props, {
      context: context
    }));
  } // Generate a class name for the block's editable form


  var generatedClassName = (0, _blocks.hasBlockSupport)(blockType, 'className', true) ? (0, _blocks.getBlockDefaultClassName)(name) : null;
  var className = (0, _classnames.default)(generatedClassName, attributes.className);
  return (0, _element.createElement)(Component, (0, _extends2.default)({}, props, {
    context: context,
    className: className
  }));
};

exports.Edit = Edit;

var _default = (0, _components.withFilters)('editor.BlockEdit')(Edit);

exports.default = _default;
//# sourceMappingURL=edit.js.map