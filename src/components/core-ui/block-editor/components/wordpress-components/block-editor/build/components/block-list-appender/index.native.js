"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _defaultBlockAppender = _interopRequireDefault(require("../default-block-appender"));

var _style = _interopRequireDefault(require("./style.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockListAppender(_ref) {
  var blockClientIds = _ref.blockClientIds,
      rootClientId = _ref.rootClientId,
      canInsertDefaultBlock = _ref.canInsertDefaultBlock,
      isLocked = _ref.isLocked,
      CustomAppender = _ref.renderAppender,
      showSeparator = _ref.showSeparator;

  if (isLocked) {
    return null;
  }

  if (CustomAppender) {
    return (0, _element.createElement)(CustomAppender, {
      showSeparator: showSeparator
    });
  }

  if (canInsertDefaultBlock) {
    return (0, _element.createElement)(_defaultBlockAppender.default, {
      rootClientId: rootClientId,
      lastBlockClientId: (0, _lodash.last)(blockClientIds),
      containerStyle: _style.default.blockListAppender,
      placeholder: blockClientIds.length > 0 ? '' : null,
      showSeparator: showSeparator
    });
  }

  return null;
}

var _default = (0, _data.withSelect)(function (select, _ref2) {
  var rootClientId = _ref2.rootClientId;

  var _select = select('core/block-editor'),
      getBlockOrder = _select.getBlockOrder,
      canInsertBlockType = _select.canInsertBlockType,
      getTemplateLock = _select.getTemplateLock;

  return {
    isLocked: !!getTemplateLock(rootClientId),
    blockClientIds: getBlockOrder(rootClientId),
    canInsertDefaultBlock: canInsertBlockType((0, _blocks.getDefaultBlockName)(), rootClientId)
  };
})(BlockListAppender);

exports.default = _default;
//# sourceMappingURL=index.native.js.map