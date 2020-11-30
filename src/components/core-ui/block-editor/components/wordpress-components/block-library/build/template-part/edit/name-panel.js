"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = TemplatePartNamePanel;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _coreData = require("@wordpress/core-data");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _url = require("@wordpress/url");

/**
 * WordPress dependencies
 */
function TemplatePartNamePanel(_ref) {
  var postId = _ref.postId,
      setAttributes = _ref.setAttributes;

  var _useEntityProp = (0, _coreData.useEntityProp)('postType', 'wp_template_part', 'title', postId),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 2),
      title = _useEntityProp2[0],
      setTitle = _useEntityProp2[1];

  var _useEntityProp3 = (0, _coreData.useEntityProp)('postType', 'wp_template_part', 'slug', postId),
      _useEntityProp4 = (0, _slicedToArray2.default)(_useEntityProp3, 2),
      slug = _useEntityProp4[0],
      setSlug = _useEntityProp4[1];

  var _useEntityProp5 = (0, _coreData.useEntityProp)('postType', 'wp_template_part', 'status', postId),
      _useEntityProp6 = (0, _slicedToArray2.default)(_useEntityProp5, 2),
      status = _useEntityProp6[0],
      setStatus = _useEntityProp6[1];

  return (0, _element.createElement)("div", {
    className: "wp-block-template-part__name-panel"
  }, (0, _element.createElement)(_components.TextControl, {
    label: (0, _i18n.__)('Name'),
    value: title || slug,
    onChange: function onChange(value) {
      setTitle(value);
      var newSlug = (0, _url.cleanForSlug)(value);
      setSlug(newSlug);

      if (status !== 'publish') {
        setStatus('publish');
      }

      setAttributes({
        slug: newSlug,
        postId: postId
      });
    },
    onFocus: function onFocus(event) {
      return event.target.select();
    }
  }));
}
//# sourceMappingURL=name-panel.js.map