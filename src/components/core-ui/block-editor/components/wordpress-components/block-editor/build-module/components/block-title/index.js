/**
 * External dependencies
 */
import { truncate } from 'lodash';
/**
 * WordPress dependencies
 */

import { useSelect } from '@wordpress/data';
import { getBlockType, __experimentalGetBlockLabel as getBlockLabel } from '@wordpress/blocks';
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

export default function BlockTitle(_ref) {
  var clientId = _ref.clientId;

  var _useSelect = useSelect(function (select) {
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

  var blockType = getBlockType(name);

  if (!blockType) {
    return null;
  }

  var title = blockType.title;
  var label = getBlockLabel(blockType, attributes); // Label will often fall back to the title if no label is defined for the
  // current label context. We do not want "Paragraph: Paragraph".

  if (label !== title) {
    return "".concat(title, ": ").concat(truncate(label, {
      length: 15
    }));
  }

  return title;
}
//# sourceMappingURL=index.js.map