import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { clone } from 'lodash';
/**
 * WordPress dependencies
 */

import { applyFilters, hasFilter } from '@wordpress/hooks';
import { Autocomplete } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { getDefaultBlockName } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import { useBlockEditContext } from '../block-edit/context';
import blockAutocompleter from '../../autocompleters/block';
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
  var _useBlockEditContext = useBlockEditContext(),
      name = _useBlockEditContext.name;

  var _props$completers = props.completers,
      completers = _props$completers === void 0 ? EMPTY_ARRAY : _props$completers;
  completers = useMemo(function () {
    var filteredCompleters = completers;

    if (name === getDefaultBlockName()) {
      filteredCompleters = filteredCompleters.concat([blockAutocompleter]);
    }

    if (hasFilter('editor.Autocomplete.completers')) {
      // Provide copies so filters may directly modify them.
      if (filteredCompleters === completers) {
        filteredCompleters = filteredCompleters.map(clone);
      }

      filteredCompleters = applyFilters('editor.Autocomplete.completers', filteredCompleters, name);
    }

    return filteredCompleters;
  }, [completers, name]);
  return createElement(Autocomplete, _extends({}, props, {
    completers: completers
  }));
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/autocomplete/README.md
 */


export default BlockEditorAutocomplete;
//# sourceMappingURL=index.js.map