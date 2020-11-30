import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, blockDefault } from '@wordpress/icons';

function InserterNoResults() {
  return createElement("div", {
    className: "block-editor-inserter__no-results"
  }, createElement(Icon, {
    className: "block-editor-inserter__no-results-icon",
    icon: blockDefault
  }), createElement("p", null, __('No results found.')));
}

export default InserterNoResults;
//# sourceMappingURL=no-results.js.map