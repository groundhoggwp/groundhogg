"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getValidAlignments = getValidAlignments;
exports.addAttribute = addAttribute;
exports.addAssignedAlign = addAssignedAlign;
exports.withDataAlign = exports.withToolbarControls = exports.AlignmentHookSettingsProvider = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _hooks = require("@wordpress/hooks");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _components = require("../components");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * An array which includes all possible valid alignments,
 * used to validate if an alignment is valid or not.
 *
 * @constant
 * @type {string[]}
 */
var ALL_ALIGNMENTS = ['left', 'center', 'right', 'wide', 'full'];
/**
 * An array which includes all wide alignments.
 * In order for this alignments to be valid they need to be supported by the block,
 * and by the theme.
 *
 * @constant
 * @type {string[]}
 */

var WIDE_ALIGNMENTS = ['wide', 'full'];
/**
 * Returns the valid alignments.
 * Takes into consideration the aligns supported by a block, if the block supports wide controls or not and if theme supports wide controls or not.
 * Exported just for testing purposes, not exported outside the module.
 *
 * @param {?boolean|string[]} blockAlign          Aligns supported by the block.
 * @param {?boolean}          hasWideBlockSupport True if block supports wide alignments. And False otherwise.
 * @param {?boolean}          hasWideEnabled      True if theme supports wide alignments. And False otherwise.
 *
 * @return {string[]} Valid alignments.
 */

function getValidAlignments(blockAlign) {
  var hasWideBlockSupport = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
  var hasWideEnabled = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
  var validAlignments;

  if (Array.isArray(blockAlign)) {
    validAlignments = blockAlign;
  } else if (blockAlign === true) {
    // `true` includes all alignments...
    validAlignments = ALL_ALIGNMENTS;
  } else {
    validAlignments = [];
  }

  if (!hasWideEnabled || blockAlign === true && !hasWideBlockSupport) {
    return _lodash.without.apply(void 0, [validAlignments].concat(WIDE_ALIGNMENTS));
  }

  return validAlignments;
}
/**
 * Filters registered block settings, extending attributes to include `align`.
 *
 * @param  {Object} settings Original block settings
 * @return {Object}          Filtered block settings
 */


function addAttribute(settings) {
  // allow blocks to specify their own attribute definition with default values if needed.
  if ((0, _lodash.has)(settings.attributes, ['align', 'type'])) {
    return settings;
  }

  if ((0, _blocks.hasBlockSupport)(settings, 'align')) {
    // Gracefully handle if settings.attributes is undefined.
    settings.attributes = _objectSpread(_objectSpread({}, settings.attributes), {}, {
      align: {
        type: 'string',
        // Allow for '' since it is used by updateAlignment function
        // in withToolbarControls for special cases with defined default values.
        enum: [].concat(ALL_ALIGNMENTS, [''])
      }
    });
  }

  return settings;
}

var AlignmentHookSettings = (0, _element.createContext)({});
/**
 * Allows to pass additional settings to the alignment hook.
 */

var AlignmentHookSettingsProvider = AlignmentHookSettings.Provider;
/**
 * Override the default edit UI to include new toolbar controls for block
 * alignment, if block defines support.
 *
 * @param  {Function} BlockEdit Original component
 * @return {Function}           Wrapped component
 */

exports.AlignmentHookSettingsProvider = AlignmentHookSettingsProvider;
var withToolbarControls = (0, _compose.createHigherOrderComponent)(function (BlockEdit) {
  return function (props) {
    var _useContext = (0, _element.useContext)(AlignmentHookSettings),
        isEmbedButton = _useContext.isEmbedButton;

    var blockName = props.name; // Compute valid alignments without taking into account,
    // if the theme supports wide alignments or not.
    // BlockAlignmentToolbar takes into account the theme support.

    var validAlignments = isEmbedButton ? [] : getValidAlignments((0, _blocks.getBlockSupport)(blockName, 'align'), (0, _blocks.hasBlockSupport)(blockName, 'alignWide', true));

    var updateAlignment = function updateAlignment(nextAlign) {
      if (!nextAlign) {
        var blockType = (0, _blocks.getBlockType)(props.name);
        var blockDefaultAlign = (0, _lodash.get)(blockType, ['attributes', 'align', 'default']);

        if (blockDefaultAlign) {
          nextAlign = '';
        }
      }

      props.setAttributes({
        align: nextAlign
      });
    };

    return [validAlignments.length > 0 && props.isSelected && (0, _element.createElement)(_components.BlockControls, {
      key: "align-controls"
    }, (0, _element.createElement)(_components.BlockAlignmentToolbar, {
      value: props.attributes.align,
      onChange: updateAlignment,
      controls: validAlignments
    })), (0, _element.createElement)(BlockEdit, (0, _extends2.default)({
      key: "edit"
    }, props))];
  };
}, 'withToolbarControls');
/**
 * Override the default block element to add alignment wrapper props.
 *
 * @param  {Function} BlockListBlock Original component
 * @return {Function}                Wrapped component
 */

exports.withToolbarControls = withToolbarControls;
var withDataAlign = (0, _compose.createHigherOrderComponent)(function (BlockListBlock) {
  return function (props) {
    var name = props.name,
        attributes = props.attributes;
    var align = attributes.align;
    var hasWideEnabled = (0, _data.useSelect)(function (select) {
      return !!select('core/block-editor').getSettings().alignWide;
    }, []); // If an alignment is not assigned, there's no need to go through the
    // effort to validate or assign its value.

    if (align === undefined) {
      return (0, _element.createElement)(BlockListBlock, props);
    }

    var validAlignments = getValidAlignments((0, _blocks.getBlockSupport)(name, 'align'), (0, _blocks.hasBlockSupport)(name, 'alignWide', true), hasWideEnabled);
    var wrapperProps = props.wrapperProps;

    if (validAlignments.includes(align)) {
      wrapperProps = _objectSpread(_objectSpread({}, wrapperProps), {}, {
        'data-align': align
      });
    }

    return (0, _element.createElement)(BlockListBlock, (0, _extends2.default)({}, props, {
      wrapperProps: wrapperProps
    }));
  };
});
/**
 * Override props assigned to save component to inject alignment class name if
 * block supports it.
 *
 * @param  {Object} props      Additional props applied to save element
 * @param  {Object} blockType  Block type
 * @param  {Object} attributes Block attributes
 * @return {Object}            Filtered props applied to save element
 */

exports.withDataAlign = withDataAlign;

function addAssignedAlign(props, blockType, attributes) {
  var align = attributes.align;
  var blockAlign = (0, _blocks.getBlockSupport)(blockType, 'align');
  var hasWideBlockSupport = (0, _blocks.hasBlockSupport)(blockType, 'alignWide', true); // Compute valid alignments without taking into account if
  // the theme supports wide alignments or not.
  // This way changing themes does not impact the block save.

  var isAlignValid = getValidAlignments(blockAlign, hasWideBlockSupport).includes(align);

  if (isAlignValid) {
    props.className = (0, _classnames.default)("align".concat(align), props.className);
  }

  return props;
}

(0, _hooks.addFilter)('blocks.registerBlockType', 'core/align/addAttribute', addAttribute);
(0, _hooks.addFilter)('editor.BlockListBlock', 'core/editor/align/with-data-align', withDataAlign);
(0, _hooks.addFilter)('editor.BlockEdit', 'core/editor/align/with-toolbar-controls', withToolbarControls);
(0, _hooks.addFilter)('blocks.getSaveContent.extraProps', 'core/align/addAssignedAlign', addAssignedAlign);
//# sourceMappingURL=align.js.map