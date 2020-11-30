"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _lodash = require("lodash");

var _hooks = require("@wordpress/hooks");

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

var _context = require("../block-edit/context");

var _block = _interopRequireDefault(require("../../autocompleters/block"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Shared reference to an empty array for cases where it is important to avoid
 * returning a new array reference on every invocation.
 *
 * @type {Array}
 */
var EMPTY_ARRAY = [];
/**
 * Wrap the default Autocomplete component with one that supports a filter hook
 * for customizing its list of autocompleters.
 *
 * @type {import('react').FC}
 */

function BlockEditorAutocomplete(props) {
  var _useBlockEditContext = (0, _context.useBlockEditContext)(),
      name = _useBlockEditContext.name;

  var _props$completers = props.completers,
      completers = _props$completers === void 0 ? EMPTY_ARRAY : _props$completers;
  completers = (0, _element.useMemo)(function () {
    var filteredCompleters = completers;

    if (name === (0, _blocks.getDefaultBlockName)()) {
      filteredCompleters = filteredCompleters.concat([_block.default]);
    }

    if ((0, _hooks.hasFilter)('editor.Autocomplete.completers')) {
      // Provide copies so filters may directly modify them.
      if (filteredCompleters === completers) {
        filteredCompleters = filteredCompleters.map(_lodash.clone);
      }

      filteredCompleters = (0, _hooks.applyFilters)('editor.Autocomplete.completers', filteredCompleters, name);
    }

    return filteredCompleters;
  }, [completers, name]);
  return (0, _element.createElement)(_components.Autocomplete, (0, _extends2.default)({}, props, {
    completers: completers
  }));
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/autocomplete/README.md
 */


var _default = BlockEditorAutocomplete;
exports.default = _default;
//# sourceMappingURL=index.js.map