import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
export default function NextPageEdit() {
  return createElement("div", {
    className: "wp-block-nextpage"
  }, createElement("span", null, __('Page break')));
}
//# sourceMappingURL=edit.js.map