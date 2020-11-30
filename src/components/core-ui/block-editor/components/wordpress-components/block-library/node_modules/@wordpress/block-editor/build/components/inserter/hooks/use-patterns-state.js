"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _lodash = require("lodash");

var _element = require("@wordpress/element");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Retrieves the block patterns inserter state.
 *
 * @param {Function} onInsert function called when inserter a list of blocks.
 *
 * @return {Array} Returns the patterns state. (patterns, categories, onSelect handler)
 */
var usePatternsState = function usePatternsState(onInsert) {
  var _useSelect = (0, _data.useSelect)(function (select) {
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

  var _useDispatch = (0, _data.useDispatch)('core/notices'),
      createSuccessNotice = _useDispatch.createSuccessNotice;

  var onClickPattern = (0, _element.useCallback)(function (pattern, blocks) {
    onInsert((0, _lodash.map)(blocks, function (block) {
      return (0, _blocks.cloneBlock)(block);
    }), pattern.name);
    createSuccessNotice((0, _i18n.sprintf)(
    /* translators: %s: block pattern title. */
    (0, _i18n.__)('Block pattern "%s" inserted.'), pattern.title), {
      type: 'snackbar'
    });
  }, []);
  return [patterns, patternCategories, onClickPattern];
};

var _default = usePatternsState;
exports.default = _default;
//# sourceMappingURL=use-patterns-state.js.map