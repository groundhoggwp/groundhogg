"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _buttonBlockAppender = _interopRequireDefault(require("./button-block-appender"));

var _defaultBlockAppender = _interopRequireDefault(require("./default-block-appender"));

var _useNestedSettingsUpdate = _interopRequireDefault(require("./use-nested-settings-update"));

var _useInnerBlockTemplateSync = _interopRequireDefault(require("./use-inner-block-template-sync"));

var _getBlockContext = _interopRequireDefault(require("./get-block-context"));

var _blockList = _interopRequireDefault(require("../block-list"));

var _context = require("../block-edit/context");

var _useBlockSync = _interopRequireDefault(require("../provider/use-block-sync"));

var _blockContext = require("../block-context");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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
  var block = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getBlock(clientId);
  }, [clientId]) || {
    innerBlocks: []
  };
  (0, _useNestedSettingsUpdate.default)(clientId, allowedBlocks, templateLock);
  (0, _useInnerBlockTemplateSync.default)(clientId, template, templateLock, templateInsertUpdatesSelection);
  var blockList = (0, _element.createElement)(_blockList.default, {
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

  var blockType = (0, _blocks.getBlockType)(block.name);

  if (blockType && blockType.providesContext) {
    var context = (0, _getBlockContext.default)(block.attributes, blockType);
    blockList = (0, _element.createElement)(_blockContext.BlockContextProvider, {
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
  (0, _useBlockSync.default)(props);
  return (0, _element.createElement)(UncontrolledInnerBlocks, props);
}
/**
 * Wrapped InnerBlocks component which detects whether to use the controlled or
 * uncontrolled variations of the InnerBlocks component. This is the component
 * which should be used throughout the application.
 *
 * @param {Object} props The component props.
 */


var InnerBlocks = function InnerBlocks(props) {
  var _useBlockEditContext = (0, _context.useBlockEditContext)(),
      clientId = _useBlockEditContext.clientId;

  var allProps = _objectSpread({
    clientId: clientId
  }, props); // Detects if the InnerBlocks should be controlled by an incoming value.


  return props.value && props.onChange ? (0, _element.createElement)(ControlledInnerBlocks, allProps) : (0, _element.createElement)(UncontrolledInnerBlocks, allProps);
}; // Expose default appender placeholders as components.


InnerBlocks.DefaultBlockAppender = _defaultBlockAppender.default;
InnerBlocks.ButtonBlockAppender = _buttonBlockAppender.default;
InnerBlocks.Content = (0, _blocks.withBlockContentContext)(function (_ref) {
  var BlockContent = _ref.BlockContent;
  return (0, _element.createElement)(BlockContent, null);
});
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/inner-blocks/README.md
 */

var _default = InnerBlocks;
exports.default = _default;
//# sourceMappingURL=index.native.js.map