import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { BACKSPACE, DELETE } from '@wordpress/keycodes';
import { getBlockType, __experimentalGetAccessibleBlockLabel as getAccessibleBlockLabel } from '@wordpress/blocks';
import { speak } from '@wordpress/a11y';
/**
 * Internal dependencies
 */

import BlockTitle from '../block-title';
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
      props = _objectWithoutProperties(_ref, ["clientId", "rootClientId"]);

  var selected = useSelect(function (select) {
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

  var _useDispatch = useDispatch('core/block-editor'),
      setNavigationMode = _useDispatch.setNavigationMode,
      removeBlock = _useDispatch.removeBlock;

  var ref = useRef(); // Focus the breadcrumb in navigation mode.

  useEffect(function () {
    ref.current.focus(); // NVDA on windows suffers from a bug where focus changes are not announced properly
    // See WordPress/gutenberg#24121 and nvaccess/nvda#5825 for more details
    // To solve it we announce the focus change manually.

    if (isWindows()) {
      speak(label);
    }
  }, []);

  function onKeyDown(event) {
    var keyCode = event.keyCode;

    if (keyCode === BACKSPACE || keyCode === DELETE) {
      removeBlock(clientId);
      event.preventDefault();
    }
  }

  var blockType = getBlockType(name);
  var label = getAccessibleBlockLabel(blockType, attributes, index + 1, orientation);
  var classNames = classnames('block-editor-block-list__block-selection-button', {
    'is-block-moving-mode': !!blockMovingMode
  });
  return createElement("div", _extends({
    className: classNames
  }, props), createElement(Button, {
    ref: ref,
    onClick: function onClick() {
      return setNavigationMode(false);
    },
    onKeyDown: onKeyDown,
    label: label
  }, createElement(BlockTitle, {
    clientId: clientId
  })));
}

export default BlockSelectionButton;
//# sourceMappingURL=block-selection-button.js.map