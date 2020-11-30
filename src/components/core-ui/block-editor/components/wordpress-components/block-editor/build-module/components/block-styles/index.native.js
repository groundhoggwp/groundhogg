import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { ScrollView } from 'react-native';
import { find } from 'lodash';
/**
 * WordPress dependencies
 */

import { useSelect, useDispatch } from '@wordpress/data';
import { _x } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import { getActiveStyle, replaceActiveStyle } from './utils';
import StylePreview from './preview';
import containerStyles from './style.scss';

function BlockStyles(_ref) {
  var clientId = _ref.clientId,
      url = _ref.url;

  var selector = function selector(select) {
    var _select = select('core/block-editor'),
        getBlock = _select.getBlock;

    var _select2 = select('core/blocks'),
        getBlockStyles = _select2.getBlockStyles;

    var block = getBlock(clientId);
    return {
      styles: getBlockStyles(block.name),
      className: block.attributes.className || ''
    };
  };

  var _useSelect = useSelect(selector, [clientId]),
      styles = _useSelect.styles,
      className = _useSelect.className;

  var _useDispatch = useDispatch('core/block-editor'),
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  if (!styles || styles.length === 0) {
    return null;
  }

  var renderedStyles = find(styles, 'isDefault') ? styles : [{
    name: 'default',
    label: _x('Default', 'block style'),
    isDefault: true
  }].concat(_toConsumableArray(styles));
  var activeStyle = getActiveStyle(renderedStyles, className);
  return createElement(ScrollView, {
    horizontal: true,
    showsHorizontalScrollIndicator: false,
    contentContainerStyle: containerStyles.content
  }, renderedStyles.map(function (style) {
    var styleClassName = replaceActiveStyle(className, activeStyle, style);
    var isActive = activeStyle === style;

    var onStylePress = function onStylePress() {
      updateBlockAttributes(clientId, {
        className: styleClassName
      });
    };

    return createElement(StylePreview, {
      onPress: onStylePress,
      isActive: isActive,
      key: style.name,
      style: style,
      url: url
    });
  }));
}

export default BlockStyles;
//# sourceMappingURL=index.native.js.map