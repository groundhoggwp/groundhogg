"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _keycodes = require("@wordpress/keycodes");

var _blocks = require("@wordpress/blocks");

var _a11y = require("@wordpress/a11y");

var _blockTitle = _interopRequireDefault(require("../block-title"));

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
 * Returns true if the user is using windows.
 *
 * @return {boolean} Whether the user is using Windows.
 */
function isWindows() {
  return window.navigator.platform.indexOf('Win') > -1;
}
/**
 * Block selection button component, displaying the label of the block. If the block
 * descends from a root block, a button is displayed enabling the user to select
 * the root block.
 *
 * @param {string} props          Component props.
 * @param {string} props.clientId Client ID of block.
 *
 * @return {WPComponent} The component to be rendered.
 */


function BlockSelectionButton(_ref) {
  var clientId = _ref.clientId,
      rootClientId = _ref.rootClientId,
      props = (0, _objectWithoutProperties2.default)(_ref, ["clientId", "rootClientId"]);
  var selected = (0, _data.useSelect)(function (select) {
    var _getBlockListSettings;

    var _select = select('core/block-editor'),
        __unstableGetBlockWithoutInnerBlocks = _select.__unstableGetBlockWithoutInnerBlocks,
        getBlockIndex = _select.getBlockIndex,
        hasBlockMovingClientId = _select.hasBlockMovingClientId,
        getBlockListSettings = _select.getBlockListSettings;

    var index = getBlockIndex(clientId, rootClientId);

    var _unstableGetBlockWit = __unstableGetBlockWithoutInnerBlocks(clientId),
        name = _unstableGetBlockWit.name,
        attributes = _unstableGetBlockWit.attributes;

    var blockMovingMode = hasBlockMovingClientId();
    return {
      index: index,
      name: name,
      attributes: attributes,
      blockMovingMode: blockMovingMode,
      orientation: (_getBlockListSettings = getBlockListSettings(rootClientId)) === null || _getBlockListSettings === void 0 ? void 0 : _getBlockListSettings.orientation
    };
  }, [clientId, rootClientId]);
  var index = selected.index,
      name = selected.name,
      attributes = selected.attributes,
      blockMovingMode = selected.blockMovingMode,
      orientation = selected.orientation;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      setNavigationMode = _useDispatch.setNavigationMode,
      removeBlock = _useDispatch.removeBlock;

  var ref = (0, _element.useRef)(); // Focus the breadcrumb in navigation mode.

  (0, _element.useEffect)(function () {
    ref.current.focus(); // NVDA on windows suffers from a bug where focus changes are not announced properly
    // See WordPress/gutenberg#24121 and nvaccess/nvda#5825 for more details
    // To solve it we announce the focus change manually.

    if (isWindows()) {
      (0, _a11y.speak)(label);
    }
  }, []);

  function onKeyDown(event) {
    var keyCode = event.keyCode;

    if (keyCode === _keycodes.BACKSPACE || keyCode === _keycodes.DELETE) {
      removeBlock(clientId);
      event.preventDefault();
    }
  }

  var blockType = (0, _blocks.getBlockType)(name);
  var label = (0, _blocks.__experimentalGetAccessibleBlockLabel)(blockType, attributes, index + 1, orientation);
  var classNames = (0, _classnames.default)('block-editor-block-list__block-selection-button', {
    'is-block-moving-mode': !!blockMovingMode
  });
  return (0, _element.createElement)("div", (0, _extends2.default)({
    className: classNames
  }, props), (0, _element.createElement)(_components.Button, {
    ref: ref,
    onClick: function onClick() {
      return setNavigationMode(false);
    },
    onKeyDown: onKeyDown,
    label: label
  }, (0, _element.createElement)(_blockTitle.default, {
    clientId: clientId
  })));
}

var _default = BlockSelectionButton;
exports.default = _default;
//# sourceMappingURL=block-selection-button.js.map