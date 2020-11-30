/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
export var getBlockPositionDescription = function getBlockPositionDescription(position, siblingCount, level) {
  return sprintf(
  /* translators: 1: The numerical position of the block. 2: The total number of blocks. 3. The level of nesting for the block. */
  __('Block %1$d of %2$d, Level %3$d'), position, siblingCount, level);
};
//# sourceMappingURL=utils.js.map