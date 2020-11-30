"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockTitle;

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Renders the block's configured title as a string, or empty if the title
 * cannot be determined.
 *
 * @example
 *
 * ```jsx
 * <BlockTitle clientId="afd1cb17-2c08-4e7a-91be-007ba7ddc3a1" />
 * ```
 *
 * @param {Object} props
 * @param {string} props.clientId Client ID of block.
 *
 * @return {?string} Block title.
 */
function BlockTitle(_ref) {
  var clientId = _ref.clientId;

  var _useSelect = (0, _data.useSelect)(function (select) {
    if (!clientId) {
      return {};
    }

    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName,
        getBlockAttributes = _select.getBlockAttributes;

    return {
      attributes: getBlockAttributes(clientId),
      name: getBlockName(clientId)
    };
  }, [clientId]),
      attributes = _useSelect.attributes,
      name = _useSelect.name;

  if (!name) {
    return null;
  }

  var blockType = (0, _blocks.getBlockType)(name);

  if (!blockType) {
    return null;
  }

  var title = blockType.title;
  var label = (0, _blocks.__experimentalGetBlockLabel)(blockType, attributes); // Label will often fall back to the title if no label is defined for the
  // current label context. We do not want "Paragraph: Paragraph".

  if (label !== title) {
    return "".concat(title, ": ").concat((0, _lodash.truncate)(label, {
      length: 15
    }));
  }

  return title;
}
//# sourceMappingURL=index.js.map