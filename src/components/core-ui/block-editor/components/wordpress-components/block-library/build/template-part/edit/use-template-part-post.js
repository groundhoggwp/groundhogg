"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useTemplatePartPost;

var _data = require("@wordpress/data");

var _url = require("@wordpress/url");

/**
 * WordPress dependencies
 */
function useTemplatePartPost(postId, slug, theme) {
  return (0, _data.useSelect)(function (select) {
    if (postId) {
      // This is already a custom template part,
      // use its CPT post.
      return select('core').getEntityRecord('postType', 'wp_template_part', postId) && postId;
    } // This is not a custom template part,
    // load the auto-draft created from the
    // relevant file.


    if (slug && theme) {
      var cleanedSlug = (0, _url.cleanForSlug)(slug);
      var posts = select('core').getEntityRecords('postType', 'wp_template_part', {
        status: ['publish', 'auto-draft'],
        slug: cleanedSlug,
        theme: theme
      });
      var foundPosts = posts === null || posts === void 0 ? void 0 : posts.filter(function (post) {
        return post.slug === cleanedSlug && post.meta && post.meta.theme === theme;
      }); // A published post might already exist if this template part was customized elsewhere
      // or if it's part of a customized template.

      var foundPost = (foundPosts === null || foundPosts === void 0 ? void 0 : foundPosts.find(function (post) {
        return post.status === 'publish';
      })) || (foundPosts === null || foundPosts === void 0 ? void 0 : foundPosts.find(function (post) {
        return post.status === 'auto-draft';
      }));
      return foundPost === null || foundPost === void 0 ? void 0 : foundPost.id;
    }
  }, [postId, slug, theme]);
}
//# sourceMappingURL=use-template-part-post.js.map