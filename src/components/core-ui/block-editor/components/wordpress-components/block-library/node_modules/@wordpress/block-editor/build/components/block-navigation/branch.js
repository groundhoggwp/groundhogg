"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockNavigationBranch;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _block = _interopRequireDefault(require("./block"));

var _appender = _interopRequireDefault(require("./appender"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockNavigationBranch(props) {
  var blocks = props.blocks,
      selectBlock = props.selectBlock,
      selectedBlockClientId = props.selectedBlockClientId,
      showAppender = props.showAppender,
      showBlockMovers = props.showBlockMovers,
      showNestedBlocks = props.showNestedBlocks,
      parentBlockClientId = props.parentBlockClientId,
      _props$level = props.level,
      level = _props$level === void 0 ? 1 : _props$level,
      _props$terminatedLeve = props.terminatedLevels,
      terminatedLevels = _props$terminatedLeve === void 0 ? [] : _props$terminatedLeve,
      _props$path = props.path,
      path = _props$path === void 0 ? [] : _props$path;
  var isTreeRoot = !parentBlockClientId;
  var filteredBlocks = (0, _lodash.compact)(blocks);

  var itemHasAppender = function itemHasAppender(parentClientId) {
    return showAppender && !isTreeRoot && selectedBlockClientId === parentClientId;
  };

  var hasAppender = itemHasAppender(parentBlockClientId); // Add +1 to the rowCount to take the block appender into account.

  var blockCount = filteredBlocks.length;
  var rowCount = hasAppender ? blockCount + 1 : blockCount;
  var appenderPosition = rowCount;
  return (0, _element.createElement)(_element.Fragment, null, (0, _lodash.map)(filteredBlocks, function (block, index) {
    var clientId = block.clientId,
        innerBlocks = block.innerBlocks;
    var position = index + 1;
    var isLastRowAtLevel = rowCount === position;
    var updatedTerminatedLevels = isLastRowAtLevel ? [].concat((0, _toConsumableArray2.default)(terminatedLevels), [level]) : terminatedLevels;
    var updatedPath = [].concat((0, _toConsumableArray2.default)(path), [position]);
    var hasNestedBlocks = showNestedBlocks && !!innerBlocks && !!innerBlocks.length;
    var hasNestedAppender = itemHasAppender(clientId);
    return (0, _element.createElement)(_element.Fragment, {
      key: clientId
    }, (0, _element.createElement)(_block.default, {
      block: block,
      onClick: selectBlock,
      isSelected: selectedBlockClientId === clientId,
      level: level,
      position: position,
      rowCount: rowCount,
      siblingBlockCount: blockCount,
      showBlockMovers: showBlockMovers,
      terminatedLevels: terminatedLevels,
      path: updatedPath
    }), (hasNestedBlocks || hasNestedAppender) && (0, _element.createElement)(BlockNavigationBranch, {
      blocks: innerBlocks,
      selectedBlockClientId: selectedBlockClientId,
      selectBlock: selectBlock,
      showAppender: showAppender,
      showBlockMovers: showBlockMovers,
      showNestedBlocks: showNestedBlocks,
      parentBlockClientId: clientId,
      level: level + 1,
      terminatedLevels: updatedTerminatedLevels,
      path: updatedPath
    }));
  }), hasAppender && (0, _element.createElement)(_appender.default, {
    parentBlockClientId: parentBlockClientId,
    position: rowCount,
    rowCount: appenderPosition,
    level: level,
    terminatedLevels: terminatedLevels,
    path: [].concat((0, _toConsumableArray2.default)(path), [appenderPosition])
  }));
}

BlockNavigationBranch.defaultProps = {
  selectBlock: function selectBlock() {}
};
//# sourceMappingURL=branch.js.map