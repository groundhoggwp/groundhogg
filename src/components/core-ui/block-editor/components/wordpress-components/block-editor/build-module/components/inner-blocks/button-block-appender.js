import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import BaseButtonBlockAppender from '../button-block-appender';
import withClientId from './with-client-id';
export var ButtonBlockAppender = function ButtonBlockAppender(_ref) {
  var clientId = _ref.clientId,
      showSeparator = _ref.showSeparator,
      isFloating = _ref.isFloating,
      onAddBlock = _ref.onAddBlock;
  return createElement(BaseButtonBlockAppender, {
    rootClientId: clientId,
    showSeparator: showSeparator,
    isFloating: isFloating,
    onAddBlock: onAddBlock
  });
};
export default withClientId(ButtonBlockAppender);
//# sourceMappingURL=button-block-appender.js.map