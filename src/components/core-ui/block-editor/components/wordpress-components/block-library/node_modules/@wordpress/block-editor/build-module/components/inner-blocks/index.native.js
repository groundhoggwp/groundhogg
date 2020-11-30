import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { getBlockType, withBlockContentContext } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import ButtonBlockAppender from './button-block-appender';
import DefaultBlockAppender from './default-block-appender';
import useNestedSettingsUpdate from './use-nested-settings-update';
import useInnerBlockTemplateSync from './use-inner-block-template-sync';
import getBlockContext from './get-block-context';
/**
 * Internal dependencies
 */

import BlockList from '../block-list';
import { useBlockEditContext } from '../block-edit/context';
import useBlockSync from '../provider/use-block-sync';
import { BlockContextProvider } from '../block-context';
/**
 * InnerBlocks is a component which allows a single block to have multiple blocks
 * as children. The UncontrolledInnerBlocks component is used whenever the inner
 * blocks are not controlled by another entity. In other words, it is normally
 * used for inner blocks in the post editor
 *
 * @param {Object} props The component props.
 */

function UncontrolledInnerBlocks(props) {
  var clientId = props.clientId,
      allowedBlocks = props.allowedBlocks,
      template = props.template,
      templateLock = props.templateLock,
      templateInsertUpdatesSelection = props.templateInsertUpdatesSelection,
      orientation = props.orientation,
      renderAppender = props.renderAppender,
      renderFooterAppender = props.renderFooterAppender,
      parentWidth = props.parentWidth,
      horizontal = props.horizontal,
      contentResizeMode = props.contentResizeMode,
      contentStyle = props.contentStyle,
      onAddBlock = props.onAddBlock,
      onDeleteBlock = props.onDeleteBlock,
      marginVertical = props.marginVertical,
      marginHorizontal = props.marginHorizontal,
      horizontalAlignment = props.horizontalAlignment,
      filterInnerBlocks = props.filterInnerBlocks;
  var block = useSelect(function (select) {
    return select('core/block-editor').getBlock(clientId);
  }, [clientId]) || {
    innerBlocks: []
  };
  useNestedSettingsUpdate(clientId, allowedBlocks, templateLock);
  useInnerBlockTemplateSync(clientId, template, templateLock, templateInsertUpdatesSelection);
  var blockList = createElement(BlockList, {
    marginVertical: marginVertical,
    marginHorizontal: marginHorizontal,
    rootClientId: clientId,
    renderAppender: renderAppender,
    renderFooterAppender: renderFooterAppender,
    withFooter: false,
    orientation: orientation,
    parentWidth: parentWidth,
    horizontalAlignment: horizontalAlignment,
    horizontal: horizontal,
    contentResizeMode: contentResizeMode,
    contentStyle: contentStyle,
    onAddBlock: onAddBlock,
    onDeleteBlock: onDeleteBlock,
    filterInnerBlocks: filterInnerBlocks
  }); // Wrap context provider if (and only if) block has context to provide.

  var blockType = getBlockType(block.name);

  if (blockType && blockType.providesContext) {
    var context = getBlockContext(block.attributes, blockType);
    blockList = createElement(BlockContextProvider, {
      value: context
    }, blockList);
  }

  return blockList;
}
/**
 * The controlled inner blocks component wraps the uncontrolled inner blocks
 * component with the blockSync hook. This keeps the innerBlocks of the block in
 * the block-editor store in sync with the blocks of the controlling entity. An
 * example of an inner block controller is a template part block, which provides
 * its own blocks from the template part entity data source.
 *
 * @param {Object} props The component props.
 */


function ControlledInnerBlocks(props) {
  useBlockSync(props);
  return createElement(UncontrolledInnerBlocks, props);
}
/**
 * Wrapped InnerBlocks component which detects whether to use the controlled or
 * uncontrolled variations of the InnerBlocks component. This is the component
 * which should be used throughout the application.
 *
 * @param {Object} props The component props.
 */


var InnerBlocks = function InnerBlocks(props) {
  var _useBlockEditContext = useBlockEditContext(),
      clientId = _useBlockEditContext.clientId;

  var allProps = _objectSpread({
    clientId: clientId
  }, props); // Detects if the InnerBlocks should be controlled by an incoming value.


  return props.value && props.onChange ? createElement(ControlledInnerBlocks, allProps) : createElement(UncontrolledInnerBlocks, allProps);
}; // Expose default appender placeholders as components.


InnerBlocks.DefaultBlockAppender = DefaultBlockAppender;
InnerBlocks.ButtonBlockAppender = ButtonBlockAppender;
InnerBlocks.Content = withBlockContentContext(function (_ref) {
  var BlockContent = _ref.BlockContent;
  return createElement(BlockContent, null);
});
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/inner-blocks/README.md
 */

export default InnerBlocks;
//# sourceMappingURL=index.native.js.map