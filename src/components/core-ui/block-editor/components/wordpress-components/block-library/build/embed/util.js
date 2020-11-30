"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getClassNames = getClassNames;
exports.fallback = fallback;
exports.getAttributesFromPreview = exports.removeAspectRatioClasses = exports.createUpgradedEmbedBlock = exports.getPhotoHtml = exports.isFromWordPress = exports.findMoreSuitableBlock = exports.matchesPatterns = exports.getEmbedInfoByProvider = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _constants = require("./constants");

var _lodash = require("lodash");

var _dedupe = _interopRequireDefault(require("classnames/dedupe"));

var _memize = _interopRequireDefault(require("memize"));

var _blocks = require("@wordpress/blocks");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/embed",
  category: "embed",
  attributes: {
    url: {
      type: "string"
    },
    caption: {
      type: "string",
      source: "html",
      selector: "figcaption"
    },
    type: {
      type: "string"
    },
    providerNameSlug: {
      type: "string"
    },
    allowResponsive: {
      type: "boolean",
      "default": true
    },
    responsive: {
      type: "boolean",
      "default": false
    },
    previewable: {
      type: "boolean",
      "default": true
    }
  },
  supports: {
    align: true,
    reusable: false,
    html: false
  }
};
var DEFAULT_EMBED_BLOCK = metadata.name;
/** @typedef {import('@wordpress/blocks').WPBlockVariation} WPBlockVariation */

/**
 * Returns the embed block's information by matching the provided service provider
 *
 * @param {string} provider The embed block's provider
 * @return {WPBlockVariation} The embed block's information
 */

var getEmbedInfoByProvider = function getEmbedInfoByProvider(provider) {
  var _getBlockVariations;

  return (_getBlockVariations = (0, _blocks.getBlockVariations)(DEFAULT_EMBED_BLOCK)) === null || _getBlockVariations === void 0 ? void 0 : _getBlockVariations.find(function (_ref) {
    var name = _ref.name;
    return name === provider;
  });
};
/**
 * Returns true if any of the regular expressions match the URL.
 *
 * @param {string}   url      The URL to test.
 * @param {Array}    patterns The list of regular expressions to test agains.
 * @return {boolean} True if any of the regular expressions match the URL.
 */


exports.getEmbedInfoByProvider = getEmbedInfoByProvider;

var matchesPatterns = function matchesPatterns(url) {
  var patterns = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  return patterns.some(function (pattern) {
    return url.match(pattern);
  });
};
/**
 * Finds the block variation that should be used for the URL,
 * based on the provided URL and the variation's patterns.
 *
 * @param {string}  url The URL to test.
 * @return {WPBlockVariation} The block variation that should be used for this URL
 */


exports.matchesPatterns = matchesPatterns;

var findMoreSuitableBlock = function findMoreSuitableBlock(url) {
  var _getBlockVariations2;

  return (_getBlockVariations2 = (0, _blocks.getBlockVariations)(DEFAULT_EMBED_BLOCK)) === null || _getBlockVariations2 === void 0 ? void 0 : _getBlockVariations2.find(function (_ref2) {
    var patterns = _ref2.patterns;
    return matchesPatterns(url, patterns);
  });
};

exports.findMoreSuitableBlock = findMoreSuitableBlock;

var isFromWordPress = function isFromWordPress(html) {
  return html && html.includes('class="wp-embedded-content"');
};

exports.isFromWordPress = isFromWordPress;

var getPhotoHtml = function getPhotoHtml(photo) {
  // 100% width for the preview so it fits nicely into the document, some "thumbnails" are
  // actually the full size photo. If thumbnails not found, use full image.
  var imageUrl = photo.thumbnail_url || photo.url;
  var photoPreview = (0, _element.createElement)("p", null, (0, _element.createElement)("img", {
    src: imageUrl,
    alt: photo.title,
    width: "100%"
  }));
  return (0, _element.renderToString)(photoPreview);
};
/**
 * Creates a more suitable embed block based on the passed in props
 * and attributes generated from an embed block's preview.
 *
 * We require `attributesFromPreview` to be generated from the latest attributes
 * and preview, and because of the way the react lifecycle operates, we can't
 * guarantee that the attributes contained in the block's props are the latest
 * versions, so we require that these are generated separately.
 * See `getAttributesFromPreview` in the generated embed edit component.
 *
 * @param {Object} props                  The block's props.
 * @param {Object} [attributesFromPreview]  Attributes generated from the block's most up to date preview.
 * @return {Object|undefined} A more suitable embed block if one exists.
 */


exports.getPhotoHtml = getPhotoHtml;

var createUpgradedEmbedBlock = function createUpgradedEmbedBlock(props) {
  var _getBlockVariations3;

  var attributesFromPreview = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  var preview = props.preview,
      _props$attributes = props.attributes;
  _props$attributes = _props$attributes === void 0 ? {} : _props$attributes;
  var url = _props$attributes.url,
      providerNameSlug = _props$attributes.providerNameSlug,
      type = _props$attributes.type;
  if (!url || !(0, _blocks.getBlockType)(DEFAULT_EMBED_BLOCK)) return;
  var matchedBlock = findMoreSuitableBlock(url); // WordPress blocks can work on multiple sites, and so don't have patterns,
  // so if we're in a WordPress block, assume the user has chosen it for a WordPress URL.

  var isCurrentBlockWP = providerNameSlug === 'wordpress' || type === _constants.WP_EMBED_TYPE; // if current block is not WordPress and a more suitable block found
  // that is different from the current one, create the new matched block

  var shouldCreateNewBlock = !isCurrentBlockWP && matchedBlock && (matchedBlock.attributes.providerNameSlug !== providerNameSlug || !providerNameSlug);

  if (shouldCreateNewBlock) {
    return (0, _blocks.createBlock)(DEFAULT_EMBED_BLOCK, _objectSpread({
      url: url
    }, matchedBlock.attributes));
  }

  var wpVariation = (_getBlockVariations3 = (0, _blocks.getBlockVariations)(DEFAULT_EMBED_BLOCK)) === null || _getBlockVariations3 === void 0 ? void 0 : _getBlockVariations3.find(function (_ref3) {
    var name = _ref3.name;
    return name === 'wordpress';
  }); // We can't match the URL for WordPress embeds, we have to check the HTML instead.

  if (!wpVariation || !preview || !isFromWordPress(preview.html) || isCurrentBlockWP) {
    return;
  } // This is not the WordPress embed block so transform it into one.


  return (0, _blocks.createBlock)(DEFAULT_EMBED_BLOCK, _objectSpread(_objectSpread({
    url: url
  }, wpVariation.attributes), attributesFromPreview));
};
/**
 * Removes all previously set aspect ratio related classes and return the rest
 * existing class names.
 *
 * @param {string} existingClassNames Any existing class names.
 * @return {string} The class names without any aspect ratio related class.
 */


exports.createUpgradedEmbedBlock = createUpgradedEmbedBlock;

var removeAspectRatioClasses = function removeAspectRatioClasses(existingClassNames) {
  var aspectRatioClassNames = _constants.ASPECT_RATIOS.reduce(function (accumulator, _ref4) {
    var className = _ref4.className;
    accumulator[className] = false;
    return accumulator;
  }, {
    'wp-has-aspect-ratio': false
  });

  return (0, _dedupe.default)(existingClassNames, aspectRatioClassNames);
};
/**
 * Returns class names with any relevant responsive aspect ratio names.
 *
 * @param {string}  html               The preview HTML that possibly contains an iframe with width and height set.
 * @param {string}  existingClassNames Any existing class names.
 * @param {boolean} allowResponsive    If the responsive class names should be added, or removed.
 * @return {string} Deduped class names.
 */


exports.removeAspectRatioClasses = removeAspectRatioClasses;

function getClassNames(html) {
  var existingClassNames = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  var allowResponsive = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;

  if (!allowResponsive) {
    return removeAspectRatioClasses(existingClassNames);
  }

  var previewDocument = document.implementation.createHTMLDocument('');
  previewDocument.body.innerHTML = html;
  var iframe = previewDocument.body.querySelector('iframe'); // If we have a fixed aspect iframe, and it's a responsive embed block.

  if (iframe && iframe.height && iframe.width) {
    var aspectRatio = (iframe.width / iframe.height).toFixed(2); // Given the actual aspect ratio, find the widest ratio to support it.

    for (var ratioIndex = 0; ratioIndex < _constants.ASPECT_RATIOS.length; ratioIndex++) {
      var potentialRatio = _constants.ASPECT_RATIOS[ratioIndex];

      if (aspectRatio >= potentialRatio.ratio) {
        return (0, _dedupe.default)(removeAspectRatioClasses(existingClassNames), potentialRatio.className, 'wp-has-aspect-ratio');
      }
    }
  }

  return existingClassNames;
}
/**
 * Fallback behaviour for unembeddable URLs.
 * Creates a paragraph block containing a link to the URL, and calls `onReplace`.
 *
 * @param {string}   url       The URL that could not be embedded.
 * @param {Function} onReplace Function to call with the created fallback block.
 */


function fallback(url, onReplace) {
  var link = (0, _element.createElement)("a", {
    href: url
  }, url);
  onReplace((0, _blocks.createBlock)('core/paragraph', {
    content: (0, _element.renderToString)(link)
  }));
}
/***
 * Gets block attributes based on the preview and responsive state.
 *
 * @param {Object} preview The preview data.
 * @param {string} title The block's title, e.g. Twitter.
 * @param {Object} currentClassNames The block's current class names.
 * @param {boolean} isResponsive Boolean indicating if the block supports responsive content.
 * @param {boolean} allowResponsive Apply responsive classes to fixed size content.
 * @return {Object} Attributes and values.
 */


var getAttributesFromPreview = (0, _memize.default)(function (preview, title, currentClassNames, isResponsive) {
  var allowResponsive = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : true;

  if (!preview) {
    return {};
  }

  var attributes = {}; // Some plugins only return HTML with no type info, so default this to 'rich'.

  var _preview$type = preview.type,
      type = _preview$type === void 0 ? 'rich' : _preview$type; // If we got a provider name from the API, use it for the slug, otherwise we use the title,
  // because not all embed code gives us a provider name.

  var html = preview.html,
      providerName = preview.provider_name;
  var providerNameSlug = (0, _lodash.kebabCase)((providerName || title).toLowerCase());

  if (isFromWordPress(html)) {
    type = _constants.WP_EMBED_TYPE;
  }

  if (html || 'photo' === type) {
    attributes.type = type;
    attributes.providerNameSlug = providerNameSlug;
  }

  attributes.className = getClassNames(html, currentClassNames, isResponsive && allowResponsive);
  return attributes;
});
exports.getAttributesFromPreview = getAttributesFromPreview;
//# sourceMappingURL=util.js.map