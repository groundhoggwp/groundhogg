import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import BlockIcon from '../block-icon';

function BlockCard(_ref) {
  var blockType = _ref.blockType;
  return createElement("div", {
    className: "block-editor-block-card"
  }, createElement(BlockIcon, {
    icon: blockType.icon,
    showColors: true
  }), createElement("div", {
    className: "block-editor-block-card__content"
  }, createElement("h2", {
    className: "block-editor-block-card__title"
  }, blockType.title), createElement("span", {
    className: "block-editor-block-card__description"
  }, blockType.description)));
}

export default BlockCard;
//# sourceMappingURL=index.js.map