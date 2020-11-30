import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { category as categoryIcon, mapMarker as linkIcon, page as pageIcon, postTitle as postIcon, tag as tagIcon } from '@wordpress/icons';
import { InnerBlocks } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/navigation-link",
  category: "design",
  parent: ["core/navigation"],
  attributes: {
    label: {
      type: "string"
    },
    type: {
      type: "string"
    },
    description: {
      type: "string"
    },
    rel: {
      type: "string"
    },
    id: {
      type: "number"
    },
    opensInNewTab: {
      type: "boolean",
      "default": false
    },
    url: {
      type: "string"
    },
    title: {
      type: "string"
    }
  },
  usesContext: ["textColor", "customTextColor", "backgroundColor", "customBackgroundColor", "fontSize", "customFontSize", "showSubmenuIcon"],
  supports: {
    reusable: false,
    html: false,
    lightBlockWrapper: true
  }
};
import edit from './edit';
import save from './save';
var name = metadata.name;
export { metadata, name };
export var settings = {
  title: __('Link'),
  icon: linkIcon,
  description: __('Add a page, link, or another item to your navigation.'),
  variations: [{
    name: 'link',
    isDefault: true,
    title: __('Link'),
    description: __('A link to a URL.'),
    attributes: {}
  }, {
    name: 'post',
    icon: postIcon,
    title: __('Post Link'),
    description: __('A link to a post.'),
    attributes: {
      type: 'post'
    }
  }, {
    name: 'page',
    icon: pageIcon,
    title: __('Page Link'),
    description: __('A link to a page.'),
    attributes: {
      type: 'page'
    }
  }, {
    name: 'category',
    icon: categoryIcon,
    title: __('Category Link'),
    description: __('A link to a category.'),
    attributes: {
      type: 'category'
    }
  }, {
    name: 'tag',
    icon: tagIcon,
    title: __('Tag Link'),
    description: __('A link to a tag.'),
    attributes: {
      type: 'tag'
    }
  }],
  __experimentalLabel: function __experimentalLabel(_ref) {
    var label = _ref.label;
    return label;
  },
  merge: function merge(leftAttributes, _ref2) {
    var _ref2$label = _ref2.label,
        rightLabel = _ref2$label === void 0 ? '' : _ref2$label;
    return _objectSpread(_objectSpread({}, leftAttributes), {}, {
      label: leftAttributes.label + rightLabel
    });
  },
  edit: edit,
  save: save,
  deprecated: [{
    isEligible: function isEligible(attributes) {
      return attributes.nofollow;
    },
    attributes: {
      label: {
        type: 'string'
      },
      type: {
        type: 'string'
      },
      nofollow: {
        type: 'boolean'
      },
      description: {
        type: 'string'
      },
      id: {
        type: 'number'
      },
      opensInNewTab: {
        type: 'boolean',
        default: false
      },
      url: {
        type: 'string'
      }
    },
    migrate: function migrate(_ref3) {
      var nofollow = _ref3.nofollow,
          rest = _objectWithoutProperties(_ref3, ["nofollow"]);

      return _objectSpread({
        rel: nofollow ? 'nofollow' : ''
      }, rest);
    },
    save: function save() {
      return createElement(InnerBlocks.Content, null);
    }
  }]
};
//# sourceMappingURL=index.js.map