"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classnames = _interopRequireDefault(require("classnames"));

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _buttonBlockAppender = _interopRequireDefault(require("./button-block-appender"));

var _defaultBlockAppender = _interopRequireDefault(require("./default-block-appender"));

var _useNestedSettingsUpdate = _interopRequireDefault(require("./use-nested-settings-update"));

var _useInnerBlockTemplateSync = _interopRequireDefault(require("./use-inner-block-template-sync"));

var _getBlockContext = _interopRequireDefault(require("./get-block-context"));

var _blockList = _interopRequireDefault(require("../block-list"));

var _blockContext = require("../block-context");

var _context = require("../block-edit/context");

var _useBlockSync = _interopRequireDefault(require("../provider/use-block-sync"));

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
      forwardedRef = props.forwardedRef,
      templateInsertUpdatesSelection = props.templateInsertUpdatesSelection,
      captureToolbars = props.__experimentalCaptureToolbars,
      orientation = props.orientation;
  var isSmallScreen = (0, _compose.useViewportMatch)('medium', '<');

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlock = _select.getBlock,
        isBlockSelected = _select.isBlockSelected,
        hasSelectedInnerBlock = _select.hasSelectedInnerBlock,
        isNavigationMode = _select.isNavigationMode;

    var theBlock = getBlock(clientId);
    return {
      block: theBlock,
      hasOverlay: theBlock.name !== 'core/template' && !isBlockSelected(clientId) && !hasSelectedInnerBlock(clientId, true),
      enableClickThrough: isNavigationMode() || isSmallScreen
    };
  }),
      hasOverlay = _useSelect.hasOverlay,
      block = _useSelect.block,
      enableClickThrough = _useSelect.enableClickThrough;

  (0, _useNestedSettingsUpdate.default)(clientId, allowedBlocks, templateLock, captureToolbars, orientation);
  (0, _useInnerBlockTemplateSync.default)(clientId, template, templateLock, templateInsertUpdatesSelection);
  var classes = (0, _classnames.default)({
    'has-overlay': enableClickThrough && hasOverlay,
    'is-capturing-toolbar': captureToolbars
  });
  var blockList = (0, _element.createElement)(_blockList.default, (0, _extends2.default)({}, props, {
    ref: forwardedRef,
    rootClientId: clientId,
    className: classes
  })); // Wrap context provider if (and only if) block has context to provide.

  var blockType = (0, _blocks.getBlockType)(block.name);

  if (blockType && blockType.providesContext) {
    var context = (0, _getBlockContext.default)(block.attributes, blockType);
    blockList = (0, _element.createElement)(_blockContext.BlockContextProvider, {
      value: context
    }, blockList);
  }

  if (props.__experimentalTagName) {
    return blockList;
  }

  return (0, _element.createElement)("div", {
    className: "block-editor-inner-blocks"
  }, blockList);
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
 */


var ForwardedInnerBlocks = (0, _element.forwardRef)(function (props, ref) {
  var _useBlockEditContext = (0, _context.useBlockEditContext)(),
      clientId = _useBlockEditContext.clientId;

  var fallbackRef = (0, _element.useRef)();

  var allProps = _objectSpread({
    clientId: clientId,
    forwardedRef: ref || fallbackRef
  }, props); // Detects if the InnerBlocks should be controlled by an incoming value.


  if (props.value && props.onChange) {
    return (0, _element.createElement)(ControlledInnerBlocks, allProps);
  }

  return (0, _element.createElement)(UncontrolledInnerBlocks, allProps);
}); // Expose default appender placeholders as components.

ForwardedInnerBlocks.DefaultBlockAppender = _defaultBlockAppender.default;
ForwardedInnerBlocks.ButtonBlockAppender = _buttonBlockAppender.default;
ForwardedInnerBlocks.Content = (0, _blocks.withBlockContentContext)(function (_ref) {
  var BlockContent = _ref.BlockContent;
  return (0, _element.createElement)(BlockContent, null);
});
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/inner-blocks/README.md
 */

var _default = ForwardedInnerBlocks;
exports.default = _default;
//# sourceMappingURL=index.js.map