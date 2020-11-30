"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.DefaultBlockAppender = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _defaultBlockAppender = _interopRequireDefault(require("../default-block-appender"));

var _withClientId = _interopRequireDefault(require("./with-client-id"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var DefaultBlockAppender = function DefaultBlockAppender(_ref) {
  var clientId = _ref.clientId,
      lastBlockClientId = _ref.lastBlockClientId;
  return (0, _element.createElement)(_defaultBlockAppender.default, {
    rootClientId: clientId,
    lastBlockClientId: lastBlockClientId
  });
};

exports.DefaultBlockAppender = DefaultBlockAppender;

var _default = (0, _compose.compose)([_withClientId.default, (0, _data.withSelect)(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockOrder = _select.getBlockOrder;

  var blockClientIds = getBlockOrder(clientId);
  return {
    lastBlockClientId: (0, _lodash.last)(blockClientIds)
  };
})])(DefaultBlockAppender);

exports.default = _default;
//# sourceMappingURL=default-block-appender.js.map