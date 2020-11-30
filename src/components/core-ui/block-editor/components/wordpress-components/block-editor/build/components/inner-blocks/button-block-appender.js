"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.ButtonBlockAppender = void 0;

var _element = require("@wordpress/element");

var _buttonBlockAppender = _interopRequireDefault(require("../button-block-appender"));

var _withClientId = _interopRequireDefault(require("./with-client-id"));

/**
 * Internal dependencies
 */
var ButtonBlockAppender = function ButtonBlockAppender(_ref) {
  var clientId = _ref.clientId,
      showSeparator = _ref.showSeparator,
      isFloating = _ref.isFloating,
      onAddBlock = _ref.onAddBlock;
  return (0, _element.createElement)(_buttonBlockAppender.default, {
    rootClientId: clientId,
    showSeparator: showSeparator,
    isFloating: isFloating,
    onAddBlock: onAddBlock
  });
};

exports.ButtonBlockAppender = ButtonBlockAppender;

var _default = (0, _withClientId.default)(ButtonBlockAppender);

exports.default = _default;
//# sourceMappingURL=button-block-appender.js.map