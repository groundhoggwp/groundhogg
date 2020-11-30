/**
 * External dependencies
 */
import { map } from 'lodash';
/**
 * WordPress dependencies
 */

import { useCallback } from '@wordpress/element';
import { cloneBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
/**
 * Retrieves the block patterns inserter state.
 *
 * @param {Function} onInsert function called when inserter a list of blocks.
 *
 * @return {Array} Returns the patterns state. (patterns, categories, onSelect handler)
 */

var usePatternsState = function usePatternsState(onInsert) {
  var _useSelect = useSelect(function (select) {
    var _select$getSettings = select('core/block-editor').getSettings(),
        __experimentalBlockPatterns = _select$getSettings.__experimentalBlockPatterns,
        __experimentalBlockPatternCategories = _select$getSettings.__experimentalBlockPatternCategories;

    return {
      patterns: __experimentalBlockPatterns,
      patternCategories: __experimentalBlockPatternCategories
    };
  }, []),
      patternCategories = _useSelect.patternCategories,
      patterns = _useSelect.patterns;

  var _useDispatch = useDispatch('core/notices'),
      createSuccessNotice = _useDispatch.createSuccessNotice;

  var onClickPattern = useCallback(function (pattern, blocks) {
    onInsert(map(blocks, function (block) {
      return cloneBlock(block);
    }), pattern.name);
    createSuccessNotice(sprintf(
    /* translators: %s: block pattern title. */
    __('Block pattern "%s" inserted.'), pattern.title), {
      type: 'snackbar'
    });
  }, []);
  return [patterns, patternCategories, onClickPattern];
};

export default usePatternsState;
//# sourceMappingURL=use-patterns-state.js.map