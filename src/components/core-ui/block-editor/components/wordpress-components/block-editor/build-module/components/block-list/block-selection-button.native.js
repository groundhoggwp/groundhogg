import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Icon } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { getBlockType } from '@wordpress/blocks';
/**
 * External dependencies
 */

import { View, Text, TouchableOpacity } from 'react-native';
/**
 * Internal dependencies
 */

import BlockTitle from '../block-title';
import SubdirectorSVG from './subdirectory-icon';
import styles from './block-selection-button.scss';

var BlockSelectionButton = function BlockSelectionButton(_ref) {
  var clientId = _ref.clientId,
      blockIcon = _ref.blockIcon,
      rootClientId = _ref.rootClientId,
      rootBlockIcon = _ref.rootBlockIcon,
      isRTL = _ref.isRTL;
  return createElement(View, {
    style: [styles.selectionButtonContainer, rootClientId && styles.densedPaddingLeft]
  }, createElement(TouchableOpacity, {
    style: styles.button,
    onPress: function onPress() {
      /* Open BottomSheet with markup */
    },
    disabled: true
    /* Disable temporarily since onPress function is empty */

  }, rootClientId && rootBlockIcon && [createElement(Icon, {
    key: "parent-icon",
    size: 24,
    icon: rootBlockIcon.src,
    fill: styles.icon.color
  }), createElement(View, {
    key: "subdirectory-icon",
    style: styles.arrow
  }, createElement(SubdirectorSVG, {
    fill: styles.arrow.color,
    isRTL: isRTL
  }))], createElement(Icon, {
    size: 24,
    icon: blockIcon.src,
    fill: styles.icon.color
  }), createElement(Text, {
    maxFontSizeMultiplier: 1.25,
    ellipsizeMode: "tail",
    numberOfLines: 1,
    style: styles.selectionButtonTitle
  }, createElement(BlockTitle, {
    clientId: clientId
  }))));
};

export default compose([withSelect(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockRootClientId = _select.getBlockRootClientId,
      getBlockName = _select.getBlockName,
      getSettings = _select.getSettings;

  var blockName = getBlockName(clientId);
  var blockType = getBlockType(blockName);
  var blockIcon = blockType ? blockType.icon : {};
  var rootClientId = getBlockRootClientId(clientId);

  if (!rootClientId) {
    return {
      clientId: clientId,
      blockIcon: blockIcon
    };
  }

  var rootBlockName = getBlockName(rootClientId);
  var rootBlockType = getBlockType(rootBlockName);
  var rootBlockIcon = rootBlockType ? rootBlockType.icon : {};
  return {
    clientId: clientId,
    blockIcon: blockIcon,
    rootClientId: rootClientId,
    rootBlockIcon: rootBlockIcon,
    isRTL: getSettings().isRTL
  };
})])(BlockSelectionButton);
//# sourceMappingURL=block-selection-button.native.js.map