"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _i18n = require("@wordpress/i18n");

/**
 * WordPress dependencies
 */
var Blog = {
  // translators: title for "Blog" page template
  name: (0, _i18n.__)('Blog'),
  key: 'blog',
  icon: '📰',
  content: [{
    name: 'core/cover',
    attributes: {
      url: 'https://mgblayoutexamples.files.wordpress.com/2020/02/people-woman-coffee-meeting.jpg'
    },
    innerBlocks: [{
      name: 'core/heading',
      attributes: {
        // translators: sample content for "Blog" page template
        content: (0, _i18n.__)('Welcome to our new blog'),
        level: 1
      }
    }]
  }, {
    name: 'core/spacer',
    attributes: {
      height: 24
    }
  }, {
    name: 'core/latest-posts',
    attributes: {}
  }, {
    name: 'core/spacer',
    attributes: {
      height: 24
    }
  }, {
    name: 'core/separator',
    attributes: {}
  }]
};
var _default = Blog;
exports.default = _default;
//# sourceMappingURL=blog.native.js.map