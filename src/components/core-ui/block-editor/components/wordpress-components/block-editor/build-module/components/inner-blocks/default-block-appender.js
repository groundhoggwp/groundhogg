import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { last } from 'lodash';
/**
 * WordPress dependencies
 */

import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import BaseDefaultBlockAppender from '../default-block-appender';
import withClientId from './with-client-id';
export var DefaultBlockAppender = function DefaultBlockAppender(_ref) {
  var clientId = _ref.clientId,
      lastBlockClientId = _ref.lastBlockClientId;
  return createElement(BaseDefaultBlockAppender, {
    rootClientId: clientId,
    lastBlockClientId: lastBlockClientId
  });
};
export default compose([withClientId, withSelect(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockOrder = _select.getBlockOrder;

  var blockClientIds = getBlockOrder(clientId);
  return {
    lastBlockClientId: last(blockClientIds)
  };
})])(DefaultBlockAppender);
//# sourceMappingURL=default-block-appender.js.map