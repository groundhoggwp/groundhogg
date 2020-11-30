/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/query-pagination",
  category: "design",
  usesContext: ["queryId", "query", "queryContext"],
  supports: {
    reusable: false,
    html: false
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Query Pagination'),
  edit: edit
};
//# sourceMappingURL=index.js.map