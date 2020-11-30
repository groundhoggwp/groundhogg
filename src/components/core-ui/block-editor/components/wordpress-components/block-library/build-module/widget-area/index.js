/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/widget-area",
  category: "widgets",
  attributes: {
    id: {
      type: "string"
    },
    name: {
      type: "string"
    }
  },
  supports: {
    html: false,
    inserter: false,
    customClassName: false,
    __experimentalToolbar: false
  }
};
import edit from './edit';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Widget Area'),
  description: __('A widget area container.'),
  __experimentalLabel: function __experimentalLabel(_ref) {
    var label = _ref.name;
    return label;
  },
  edit: edit
};
//# sourceMappingURL=index.js.map