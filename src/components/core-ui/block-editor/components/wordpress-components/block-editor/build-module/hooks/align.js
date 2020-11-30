import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { get, has, without } from 'lodash';
/**
 * WordPress dependencies
 */

import { createContext, useContext } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { getBlockSupport, getBlockType, hasBlockSupport } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import { BlockControls, BlockAlignmentToolbar } from '../components';
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

export function getValidAlignments(blockAlign) {
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
    return without.apply(void 0, [validAlignments].concat(WIDE_ALIGNMENTS));
  }

  return validAlignments;
}
/**
 * Filters registered block settings, extending attributes to include `align`.
 *
 * @param  {Object} settings Original block settings
 * @return {Object}          Filtered block settings
 */

export function addAttribute(settings) {
  // allow blocks to specify their own attribute definition with default values if needed.
  if (has(settings.attributes, ['align', 'type'])) {
    return settings;
  }

  if (hasBlockSupport(settings, 'align')) {
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
var AlignmentHookSettings = createContext({});
/**
 * Allows to pass additional settings to the alignment hook.
 */

export var AlignmentHookSettingsProvider = AlignmentHookSettings.Provider;
/**
 * Override the default edit UI to include new toolbar controls for block
 * alignment, if block defines support.
 *
 * @param  {Function} BlockEdit Original component
 * @return {Function}           Wrapped component
 */

export var withToolbarControls = createHigherOrderComponent(function (BlockEdit) {
  return function (props) {
    var _useContext = useContext(AlignmentHookSettings),
        isEmbedButton = _useContext.isEmbedButton;

    var blockName = props.name; // Compute valid alignments without taking into account,
    // if the theme supports wide alignments or not.
    // BlockAlignmentToolbar takes into account the theme support.

    var validAlignments = isEmbedButton ? [] : getValidAlignments(getBlockSupport(blockName, 'align'), hasBlockSupport(blockName, 'alignWide', true));

    var updateAlignment = function updateAlignment(nextAlign) {
      if (!nextAlign) {
        var blockType = getBlockType(props.name);
        var blockDefaultAlign = get(blockType, ['attributes', 'align', 'default']);

        if (blockDefaultAlign) {
          nextAlign = '';
        }
      }

      props.setAttributes({
        align: nextAlign
      });
    };

    return [validAlignments.length > 0 && props.isSelected && createElement(BlockControls, {
      key: "align-controls"
    }, createElement(BlockAlignmentToolbar, {
      value: props.attributes.align,
      onChange: updateAlignment,
      controls: validAlignments
    })), createElement(BlockEdit, _extends({
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

export var withDataAlign = createHigherOrderComponent(function (BlockListBlock) {
  return function (props) {
    var name = props.name,
        attributes = props.attributes;
    var align = attributes.align;
    var hasWideEnabled = useSelect(function (select) {
      return !!select('core/block-editor').getSettings().alignWide;
    }, []); // If an alignment is not assigned, there's no need to go through the
    // effort to validate or assign its value.

    if (align === undefined) {
      return createElement(BlockListBlock, props);
    }

    var validAlignments = getValidAlignments(getBlockSupport(name, 'align'), hasBlockSupport(name, 'alignWide', true), hasWideEnabled);
    var wrapperProps = props.wrapperProps;

    if (validAlignments.includes(align)) {
      wrapperProps = _objectSpread(_objectSpread({}, wrapperProps), {}, {
        'data-align': align
      });
    }

    return createElement(BlockListBlock, _extends({}, props, {
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

export function addAssignedAlign(props, blockType, attributes) {
  var align = attributes.align;
  var blockAlign = getBlockSupport(blockType, 'align');
  var hasWideBlockSupport = hasBlockSupport(blockType, 'alignWide', true); // Compute valid alignments without taking into account if
  // the theme supports wide alignments or not.
  // This way changing themes does not impact the block save.

  var isAlignValid = getValidAlignments(blockAlign, hasWideBlockSupport).includes(align);

  if (isAlignValid) {
    props.className = classnames("align".concat(align), props.className);
  }

  return props;
}
addFilter('blocks.registerBlockType', 'core/align/addAttribute', addAttribute);
addFilter('editor.BlockListBlock', 'core/editor/align/with-data-align', withDataAlign);
addFilter('editor.BlockEdit', 'core/editor/align/with-toolbar-controls', withToolbarControls);
addFilter('blocks.getSaveContent.extraProps', 'core/align/addAssignedAlign', addAssignedAlign);
//# sourceMappingURL=align.js.map