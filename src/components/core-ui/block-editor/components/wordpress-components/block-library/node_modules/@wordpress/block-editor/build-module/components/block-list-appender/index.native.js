import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { last } from 'lodash';
/**
 * WordPress dependencies
 */

import { withSelect } from '@wordpress/data';
import { getDefaultBlockName } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import DefaultBlockAppender from '../default-block-appender';
import styles from './style.scss';

function BlockListAppender(_ref) {
  var blockClientIds = _ref.blockClientIds,
      rootClientId = _ref.rootClientId,
      canInsertDefaultBlock = _ref.canInsertDefaultBlock,
      isLocked = _ref.isLocked,
      CustomAppender = _ref.renderAppender,
      showSeparator = _ref.showSeparator;

  if (isLocked) {
    return null;
  }

  if (CustomAppender) {
    return createElement(CustomAppender, {
      showSeparator: showSeparator
    });
  }

  if (canInsertDefaultBlock) {
    return createElement(DefaultBlockAppender, {
      rootClientId: rootClientId,
      lastBlockClientId: last(blockClientIds),
      containerStyle: styles.blockListAppender,
      placeholder: blockClientIds.length > 0 ? '' : null,
      showSeparator: showSeparator
    });
  }

  return null;
}

export default withSelect(function (select, _ref2) {
  var rootClientId = _ref2.rootClientId;

  var _select = select('core/block-editor'),
      getBlockOrder = _select.getBlockOrder,
      canInsertBlockType = _select.canInsertBlockType,
      getTemplateLock = _select.getTemplateLock;

  return {
    isLocked: !!getTemplateLock(rootClientId),
    blockClientIds: getBlockOrder(rootClientId),
    canInsertDefaultBlock: canInsertBlockType(getDefaultBlockName(), rootClientId)
  };
})(BlockListAppender);
//# sourceMappingURL=index.native.js.map