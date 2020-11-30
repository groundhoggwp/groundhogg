import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { Platform } from 'react-native';
import { sortBy } from 'lodash';
/**
 * WordPress dependencies
 */

import { hasBlockSupport, registerBlockType, setDefaultBlockName, setFreeformContentHandlerName, setUnregisteredTypeHandlerName, setGroupingBlockName } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
/**
 * Internal dependencies
 */

import * as paragraph from './paragraph';
import * as image from './image';
import * as heading from './heading';
import * as quote from './quote';
import * as gallery from './gallery';
import * as archives from './archives';
import * as audio from './audio';
import * as button from './button';
import * as calendar from './calendar';
import * as categories from './categories';
import * as code from './code';
import * as columns from './columns';
import * as column from './column';
import * as cover from './cover';
import * as embed from './embed';
import * as file from './file';
import * as html from './html';
import * as mediaText from './media-text';
import * as latestComments from './latest-comments';
import * as latestPosts from './latest-posts';
import * as list from './list';
import * as missing from './missing';
import * as more from './more';
import * as nextpage from './nextpage';
import * as preformatted from './preformatted';
import * as pullquote from './pullquote';
import * as reusableBlock from './block';
import * as rss from './rss';
import * as search from './search';
import * as separator from './separator';
import * as shortcode from './shortcode';
import * as spacer from './spacer';
import * as subhead from './subhead';
import * as table from './table';
import * as textColumns from './text-columns';
import * as verse from './verse';
import * as video from './video';
import * as tagCloud from './tag-cloud';
import * as classic from './classic';
import * as group from './group';
import * as buttons from './buttons';
import * as socialLink from './social-link';
import * as socialLinks from './social-links';
export var coreBlocks = [// Common blocks are grouped at the top to prioritize their display
// in various contexts — like the inserter and auto-complete components.
paragraph, image, heading, gallery, list, quote, // Register all remaining core blocks.
shortcode, archives, audio, button, calendar, categories, code, columns, column, cover, embed, file, html, mediaText, latestComments, latestPosts, missing, more, nextpage, preformatted, pullquote, rss, search, separator, reusableBlock, spacer, subhead, table, tagCloud, textColumns, verse, video, classic, buttons, socialLink, socialLinks].reduce(function (accumulator, block) {
  accumulator[block.name] = block;
  return accumulator;
}, {});
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
  registerBlockType(name, _objectSpread(_objectSpread({}, metadata), settings));
};
/**
 * Function to register a block variations e.g. social icons different types.
 *
 * @param {Object} block The block which variations will be registered.
 *
 */


var registerBlockVariations = function registerBlockVariations(block) {
  var metadata = block.metadata,
      settings = block.settings,
      name = block.name;
  sortBy(settings.variations, 'title').forEach(function (v) {
    registerBlockType("".concat(name, "-").concat(v.name), _objectSpread(_objectSpread(_objectSpread({}, metadata), {}, {
      name: "".concat(name, "-").concat(v.name)
    }, settings), {}, {
      icon: v.icon(),
      title: v.title,
      variations: []
    }));
  });
}; // only enable code block for development
// eslint-disable-next-line no-undef


var devOnly = function devOnly(block) {
  return !!__DEV__ ? block : null;
}; // eslint-disable-next-line no-unused-vars


var iOSOnly = function iOSOnly(block) {
  return Platform.OS === 'ios' ? block : devOnly(block);
}; // Hide the Classic block and SocialLink block


addFilter('blocks.registerBlockType', 'core/react-native-editor', function (settings, name) {
  var hiddenBlocks = ['core/freeform', 'core/social-link'];

  if (hiddenBlocks.includes(name) && hasBlockSupport(settings, 'inserter', true)) {
    settings.supports = _objectSpread(_objectSpread({}, settings.supports), {}, {
      inserter: false
    });
  }

  return settings;
});
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

export var registerCoreBlocks = function registerCoreBlocks() {
  // When adding new blocks to this list please also consider updating /src/block-support/supported-blocks.json in the Gutenberg-Mobile repo
  [paragraph, heading, devOnly(code), missing, more, image, video, nextpage, separator, list, quote, mediaText, preformatted, gallery, columns, column, group, classic, button, spacer, shortcode, buttons, latestPosts, verse, cover, socialLink, socialLinks, pullquote].forEach(registerBlock);
  registerBlockVariations(socialLink);
  setDefaultBlockName(paragraph.name);
  setFreeformContentHandlerName(classic.name);
  setUnregisteredTypeHandlerName(missing.name);

  if (group) {
    setGroupingBlockName(group.name);
  }
};
//# sourceMappingURL=index.native.js.map