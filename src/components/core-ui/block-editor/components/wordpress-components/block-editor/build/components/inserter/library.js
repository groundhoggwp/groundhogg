"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _menu = _interopRequireDefault(require("./menu"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function InserterLibrary(_ref) {
  var rootClientId = _ref.rootClientId,
      clientId = _ref.clientId,
      isAppender = _ref.isAppender,
      showInserterHelpPanel = _ref.showInserterHelpPanel,
      _ref$showMostUsedBloc = _ref.showMostUsedBlocks,
      showMostUsedBlocks = _ref$showMostUsedBloc === void 0 ? false : _ref$showMostUsedBloc,
      selectBlockOnInsert = _ref.__experimentalSelectBlockOnInsert,
      _ref$onSelect = _ref.onSelect,
      onSelect = _ref$onSelect === void 0 ? _lodash.noop : _ref$onSelect;
  var destinationRootClientId = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockRootClientId = _select.getBlockRootClientId;

    return rootClientId || getBlockRootClientId(clientId) || undefined;
  }, [clientId, rootClientId]);
  return (0, _element.createElement)(_menu.default, {
    onSelect: onSelect,
    rootClientId: destinationRootClientId,
    clientId: clientId,
    isAppender: isAppender,
    showInserterHelpPanel: showInserterHelpPanel,
    showMostUsedBlocks: showMostUsedBlocks,
    __experimentalSelectBlockOnInsert: selectBlockOnInsert
  });
}

var _default = InserterLibrary;
exports.default = _default;
//# sourceMappingURL=library.js.map