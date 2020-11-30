"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

/**
 * WordPress dependencies
 */
var ConvertToBlocksButton = function ConvertToBlocksButton(_ref) {
  var clientId = _ref.clientId;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      replaceBlocks = _useDispatch.replaceBlocks;

  var block = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getBlock(clientId);
  }, [clientId]);
  return (0, _element.createElement)(_components.ToolbarButton, {
    onClick: function onClick() {
      return replaceBlocks(block.clientId, (0, _blocks.rawHandler)({
        HTML: (0, _blocks.serialize)(block)
      }));
    }
  }, (0, _i18n.__)('Convert to blocks'));
};

var _default = ConvertToBlocksButton;
exports.default = _default;
//# sourceMappingURL=convert-to-blocks-button.js.map