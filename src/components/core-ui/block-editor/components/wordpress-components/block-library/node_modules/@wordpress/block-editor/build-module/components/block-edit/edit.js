import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { pick } from 'lodash';
/**
 * WordPress dependencies
 */

import { withFilters } from '@wordpress/components';
import { getBlockDefaultClassName, hasBlockSupport, getBlockType } from '@wordpress/blocks';
import { useContext, useMemo } from '@wordpress/element';
/**
 * Internal dependencies
 */

import BlockContext from '../block-context';
/**
 * Default value used for blocks which do not define their own context needs,
 * used to guarantee that a block's `context` prop will always be an object. It
 * is assigned as a constant since it is always expected to be an empty object,
 * and in order to avoid unnecessary React reconciliations of a changing object.
 *
 * @type {{}}
 */

var DEFAULT_BLOCK_CONTEXT = {};
export var Edit = function Edit(props) {
  var _props$attributes = props.attributes,
      attributes = _props$attributes === void 0 ? {} : _props$attributes,
      name = props.name;
  var blockType = getBlockType(name);
  var blockContext = useContext(BlockContext); // Assign context values using the block type's declared context needs.

  var context = useMemo(function () {
    return blockType && blockType.usesContext ? pick(blockContext, blockType.usesContext) : DEFAULT_BLOCK_CONTEXT;
  }, [blockType, blockContext]);

  if (!blockType) {
    return null;
  } // `edit` and `save` are functions or components describing the markup
  // with which a block is displayed. If `blockType` is valid, assign
  // them preferentially as the render value for the block.


  var Component = blockType.edit || blockType.save;
  var lightBlockWrapper = hasBlockSupport(blockType, 'lightBlockWrapper', false);

  if (lightBlockWrapper) {
    return createElement(Component, _extends({}, props, {
      context: context
    }));
  } // Generate a class name for the block's editable form


  var generatedClassName = hasBlockSupport(blockType, 'className', true) ? getBlockDefaultClassName(name) : null;
  var className = classnames(generatedClassName, attributes.className);
  return createElement(Component, _extends({}, props, {
    context: context,
    className: className
  }));
};
export default withFilters('editor.BlockEdit')(Edit);
//# sourceMappingURL=edit.js.map