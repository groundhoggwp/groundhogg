import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

var EmbedLoading = function EmbedLoading() {
  return createElement("div", {
    className: "wp-block-embed is-loading"
  }, createElement(Spinner, null), createElement("p", null, __('Embedding…')));
};

export default EmbedLoading;
//# sourceMappingURL=embed-loading.js.map