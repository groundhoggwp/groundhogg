"use strict";

var _interopRequireWildcard = require("@babel/runtime/helpers/interopRequireWildcard");

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.__experimentalRegisterExperimentalCoreBlocks = exports.registerCoreBlocks = void 0;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

require("@wordpress/core-data");

require("@wordpress/notices");

require("@wordpress/block-editor");

var _blocks = require("@wordpress/blocks");

var paragraph = _interopRequireWildcard(require("./paragraph"));

var image = _interopRequireWildcard(require("./image"));

var heading = _interopRequireWildcard(require("./heading"));

var quote = _interopRequireWildcard(require("./quote"));

var gallery = _interopRequireWildcard(require("./gallery"));

var archives = _interopRequireWildcard(require("./archives"));

var audio = _interopRequireWildcard(require("./audio"));

var buttons = _interopRequireWildcard(require("./buttons"));

var button = _interopRequireWildcard(require("./button"));

var calendar = _interopRequireWildcard(require("./calendar"));

var categories = _interopRequireWildcard(require("./categories"));

var code = _interopRequireWildcard(require("./code"));

var columns = _interopRequireWildcard(require("./columns"));

var column = _interopRequireWildcard(require("./column"));

var cover = _interopRequireWildcard(require("./cover"));

var embed = _interopRequireWildcard(require("./embed"));

var file = _interopRequireWildcard(require("./file"));

var html = _interopRequireWildcard(require("./html"));

var mediaText = _interopRequireWildcard(require("./media-text"));

var navigation = _interopRequireWildcard(require("./navigation"));

var navigationLink = _interopRequireWildcard(require("./navigation-link"));

var latestComments = _interopRequireWildcard(require("./latest-comments"));

var latestPosts = _interopRequireWildcard(require("./latest-posts"));

var list = _interopRequireWildcard(require("./list"));

var missing = _interopRequireWildcard(require("./missing"));

var more = _interopRequireWildcard(require("./more"));

var nextpage = _interopRequireWildcard(require("./nextpage"));

var preformatted = _interopRequireWildcard(require("./preformatted"));

var pullquote = _interopRequireWildcard(require("./pullquote"));

var reusableBlock = _interopRequireWildcard(require("./block"));

var rss = _interopRequireWildcard(require("./rss"));

var search = _interopRequireWildcard(require("./search"));

var group = _interopRequireWildcard(require("./group"));

var separator = _interopRequireWildcard(require("./separator"));

var shortcode = _interopRequireWildcard(require("./shortcode"));

var spacer = _interopRequireWildcard(require("./spacer"));

var subhead = _interopRequireWildcard(require("./subhead"));

var table = _interopRequireWildcard(require("./table"));

var textColumns = _interopRequireWildcard(require("./text-columns"));

var verse = _interopRequireWildcard(require("./verse"));

var video = _interopRequireWildcard(require("./video"));

var tagCloud = _interopRequireWildcard(require("./tag-cloud"));

var classic = _interopRequireWildcard(require("./classic"));

var socialLinks = _interopRequireWildcard(require("./social-links"));

var socialLink = _interopRequireWildcard(require("./social-link"));

var widgetArea = _interopRequireWildcard(require("./widget-area"));

var siteLogo = _interopRequireWildcard(require("./site-logo"));

var siteTagline = _interopRequireWildcard(require("./site-tagline"));

var siteTitle = _interopRequireWildcard(require("./site-title"));

var templatePart = _interopRequireWildcard(require("./template-part"));

var query = _interopRequireWildcard(require("./query"));

var queryLoop = _interopRequireWildcard(require("./query-loop"));

var queryPagination = _interopRequireWildcard(require("./query-pagination"));

var postTitle = _interopRequireWildcard(require("./post-title"));

var postContent = _interopRequireWildcard(require("./post-content"));

var postAuthor = _interopRequireWildcard(require("./post-author"));

var postComment = _interopRequireWildcard(require("./post-comment"));

var postCommentAuthor = _interopRequireWildcard(require("./post-comment-author"));

var postCommentContent = _interopRequireWildcard(require("./post-comment-content"));

var postCommentDate = _interopRequireWildcard(require("./post-comment-date"));

var postComments = _interopRequireWildcard(require("./post-comments"));

var postCommentsCount = _interopRequireWildcard(require("./post-comments-count"));

var postCommentsForm = _interopRequireWildcard(require("./post-comments-form"));

var postDate = _interopRequireWildcard(require("./post-date"));

var postExcerpt = _interopRequireWildcard(require("./post-excerpt"));

var postFeaturedImage = _interopRequireWildcard(require("./post-featured-image"));

var postHierarchicalTerms = _interopRequireWildcard(require("./post-hierarchical-terms"));

var postTags = _interopRequireWildcard(require("./post-tags"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
// Full Site Editing Blocks

/**
 * Function to register an individual block.
 *
 * @param {Object} block The block to be registered.
 *
 */
var registerBlock = function registerBlock(block) {
  if (!block) {
    return;
  }

  var metadata = block.metadata,
      settings = block.settings,
      name = block.name;

  if (metadata) {
    (0, _blocks.unstable__bootstrapServerSideBlockDefinitions)((0, _defineProperty2.default)({}, name, metadata));
  }

  (0, _blocks.registerBlockType)(name, settings);
};
/**
 * Function to register core blocks provided by the block editor.
 *
 * @example
 * ```js
 * import { registerCoreBlocks } from '@wordpress/block-library';
 *
 * registerCoreBlocks();
 * ```
 */


var registerCoreBlocks = function registerCoreBlocks() {
  [// Common blocks are grouped at the top to prioritize their display
  // in various contexts — like the inserter and auto-complete components.
  paragraph, image, heading, gallery, list, quote, // Register all remaining core blocks.
  shortcode, archives, audio, button, buttons, calendar, categories, code, columns, column, cover, embed, file, group, window.wp && window.wp.oldEditor ? classic : null, // Only add the classic block in WP Context
  html, mediaText, latestComments, latestPosts, missing, more, nextpage, preformatted, pullquote, rss, search, separator, reusableBlock, socialLinks, socialLink, spacer, subhead, table, tagCloud, textColumns, verse, video].forEach(registerBlock);
  (0, _blocks.setDefaultBlockName)(paragraph.name);

  if (window.wp && window.wp.oldEditor) {
    (0, _blocks.setFreeformContentHandlerName)(classic.name);
  }

  (0, _blocks.setUnregisteredTypeHandlerName)(missing.name);
  (0, _blocks.setGroupingBlockName)(group.name);
};
/**
 * Function to register experimental core blocks depending on editor settings.
 *
 * @param {Object} settings Editor settings.
 *
 * @example
 * ```js
 * import { __experimentalRegisterExperimentalCoreBlocks } from '@wordpress/block-library';
 *
 * __experimentalRegisterExperimentalCoreBlocks( settings );
 * ```
 */


exports.registerCoreBlocks = registerCoreBlocks;

var __experimentalRegisterExperimentalCoreBlocks = process.env.GUTENBERG_PHASE === 2 ? function (settings) {
  var __experimentalEnableFullSiteEditing = settings.__experimentalEnableFullSiteEditing;
  [widgetArea, navigation, navigationLink].concat((0, _toConsumableArray2.default)(__experimentalEnableFullSiteEditing ? [siteLogo, siteTagline, siteTitle, templatePart, query, queryLoop, queryPagination, postTitle, postContent, postAuthor, postComment, postCommentAuthor, postCommentContent, postCommentDate, postComments, postCommentsCount, postCommentsForm, postDate, postExcerpt, postFeaturedImage, postHierarchicalTerms, postTags] : [])).forEach(registerBlock);
} : undefined;

exports.__experimentalRegisterExperimentalCoreBlocks = __experimentalRegisterExperimentalCoreBlocks;
//# sourceMappingURL=index.js.map