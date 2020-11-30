"use strict";

var _interopRequireWildcard = require("@babel/runtime/helpers/interopRequireWildcard");

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isUnmodifiedDefaultBlock = isUnmodifiedDefaultBlock;
exports.isValidIcon = isValidIcon;
exports.normalizeIconObject = normalizeIconObject;
exports.normalizeBlockType = normalizeBlockType;
exports.getBlockLabel = getBlockLabel;
exports.getAccessibleBlockLabel = getAccessibleBlockLabel;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _tinycolor = _interopRequireWildcard(require("tinycolor2"));

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _dom = require("@wordpress/dom");

var _registration = require("./registration");

var _factory = require("./factory");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Array of icon colors containing a color to be used if the icon color
 * was not explicitly set but the icon background color was.
 *
 * @type {Object}
 */
var ICON_COLORS = ['#191e23', '#f8f9f9'];
/**
 * Determines whether the block is a default block
 * and its attributes are equal to the default attributes
 * which means the block is unmodified.
 *
 * @param  {WPBlock} block Block Object
 *
 * @return {boolean}       Whether the block is an unmodified default block
 */

function isUnmodifiedDefaultBlock(block) {
  var defaultBlockName = (0, _registration.getDefaultBlockName)();

  if (block.name !== defaultBlockName) {
    return false;
  } // Cache a created default block if no cache exists or the default block
  // name changed.


  if (!isUnmodifiedDefaultBlock.block || isUnmodifiedDefaultBlock.block.name !== defaultBlockName) {
    isUnmodifiedDefaultBlock.block = (0, _factory.createBlock)(defaultBlockName);
  }

  var newDefaultBlock = isUnmodifiedDefaultBlock.block;
  var blockType = (0, _registration.getBlockType)(defaultBlockName);
  return (0, _lodash.every)(blockType.attributes, function (value, key) {
    return newDefaultBlock.attributes[key] === block.attributes[key];
  });
}
/**
 * Function that checks if the parameter is a valid icon.
 *
 * @param {*} icon  Parameter to be checked.
 *
 * @return {boolean} True if the parameter is a valid icon and false otherwise.
 */


function isValidIcon(icon) {
  return !!icon && ((0, _lodash.isString)(icon) || (0, _element.isValidElement)(icon) || (0, _lodash.isFunction)(icon) || icon instanceof _element.Component);
}
/**
 * Function that receives an icon as set by the blocks during the registration
 * and returns a new icon object that is normalized so we can rely on just on possible icon structure
 * in the codebase.
 *
 * @param {WPBlockTypeIconRender} icon Render behavior of a block type icon;
 *                                     one of a Dashicon slug, an element, or a
 *                                     component.
 *
 * @return {WPBlockTypeIconDescriptor} Object describing the icon.
 */


function normalizeIconObject(icon) {
  if (isValidIcon(icon)) {
    return {
      src: icon
    };
  }

  if ((0, _lodash.has)(icon, ['background'])) {
    var tinyBgColor = (0, _tinycolor.default)(icon.background);
    return _objectSpread({}, icon, {
      foreground: icon.foreground ? icon.foreground : (0, _tinycolor.mostReadable)(tinyBgColor, ICON_COLORS, {
        includeFallbackColors: true,
        level: 'AA',
        size: 'large'
      }).toHexString(),
      shadowColor: tinyBgColor.setAlpha(0.3).toRgbString()
    });
  }

  return icon;
}
/**
 * Normalizes block type passed as param. When string is passed then
 * it converts it to the matching block type object.
 * It passes the original object otherwise.
 *
 * @param {string|Object} blockTypeOrName  Block type or name.
 *
 * @return {?Object} Block type.
 */


function normalizeBlockType(blockTypeOrName) {
  if ((0, _lodash.isString)(blockTypeOrName)) {
    return (0, _registration.getBlockType)(blockTypeOrName);
  }

  return blockTypeOrName;
}
/**
 * Get the label for the block, usually this is either the block title,
 * or the value of the block's `label` function when that's specified.
 *
 * @param {Object} blockType  The block type.
 * @param {Object} attributes The values of the block's attributes.
 * @param {Object} context    The intended use for the label.
 *
 * @return {string} The block label.
 */


function getBlockLabel(blockType, attributes) {
  var context = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'visual';
  var getLabel = blockType.__experimentalLabel,
      title = blockType.title;
  var label = getLabel && getLabel(attributes, {
    context: context
  });

  if (!label) {
    return title;
  } // Strip any HTML (i.e. RichText formatting) before returning.


  return (0, _dom.__unstableStripHTML)(label);
}
/**
 * Get a label for the block for use by screenreaders, this is more descriptive
 * than the visual label and includes the block title and the value of the
 * `getLabel` function if it's specified.
 *
 * @param {Object}  blockType              The block type.
 * @param {Object}  attributes             The values of the block's attributes.
 * @param {?number} position               The position of the block in the block list.
 * @param {string}  [direction='vertical'] The direction of the block layout.
 *
 * @return {string} The block label.
 */


function getAccessibleBlockLabel(blockType, attributes, position) {
  var direction = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'vertical';
  // `title` is already localized, `label` is a user-supplied value.
  var title = blockType.title;
  var label = getBlockLabel(blockType, attributes, 'accessibility');
  var hasPosition = position !== undefined; // getBlockLabel returns the block title as a fallback when there's no label,
  // if it did return the title, this function needs to avoid adding the
  // title twice within the accessible label. Use this `hasLabel` boolean to
  // handle that.

  var hasLabel = label && label !== title;

  if (hasPosition && direction === 'vertical') {
    if (hasLabel) {
      return (0, _i18n.sprintf)(
      /* translators: accessibility text. 1: The block title. 2: The block row number. 3: The block label.. */
      (0, _i18n.__)('%1$s Block. Row %2$d. %3$s'), title, position, label);
    }

    return (0, _i18n.sprintf)(
    /* translators: accessibility text. 1: The block title. 2: The block row number. */
    (0, _i18n.__)('%1$s Block. Row %2$d'), title, position);
  } else if (hasPosition && direction === 'horizontal') {
    if (hasLabel) {
      return (0, _i18n.sprintf)(
      /* translators: accessibility text. 1: The block title. 2: The block column number. 3: The block label.. */
      (0, _i18n.__)('%1$s Block. Column %2$d. %3$s'), title, position, label);
    }

    return (0, _i18n.sprintf)(
    /* translators: accessibility text. 1: The block title. 2: The block column number. */
    (0, _i18n.__)('%1$s Block. Column %2$d'), title, position);
  }

  if (hasLabel) {
    return (0, _i18n.sprintf)(
    /* translators: accessibility text. %1: The block title. %2: The block label. */
    (0, _i18n.__)('%1$s Block. %2$s'), title, label);
  }

  return (0, _i18n.sprintf)(
  /* translators: accessibility text. %s: The block title. */
  (0, _i18n.__)('%s Block'), title);
}
//# sourceMappingURL=utils.js.map