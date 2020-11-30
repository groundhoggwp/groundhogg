import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import InserterMenu from './menu';

function InserterLibrary(_ref) {
  var rootClientId = _ref.rootClientId,
      clientId = _ref.clientId,
      isAppender = _ref.isAppender,
      showInserterHelpPanel = _ref.showInserterHelpPanel,
      _ref$showMostUsedBloc = _ref.showMostUsedBlocks,
      showMostUsedBlocks = _ref$showMostUsedBloc === void 0 ? false : _ref$showMostUsedBloc,
      selectBlockOnInsert = _ref.__experimentalSelectBlockOnInsert,
      _ref$onSelect = _ref.onSelect,
      onSelect = _ref$onSelect === void 0 ? noop : _ref$onSelect;
  var destinationRootClientId = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlockRootClientId = _select.getBlockRootClientId;

    return rootClientId || getBlockRootClientId(clientId) || undefined;
  }, [clientId, rootClientId]);
  return createElement(InserterMenu, {
    onSelect: onSelect,
    rootClientId: destinationRootClientId,
    clientId: clientId,
    isAppender: isAppender,
    showInserterHelpPanel: showInserterHelpPanel,
    showMostUsedBlocks: showMostUsedBlocks,
    __experimentalSelectBlockOnInsert: selectBlockOnInsert
  });
}

export default InserterLibrary;
//# sourceMappingURL=library.js.map