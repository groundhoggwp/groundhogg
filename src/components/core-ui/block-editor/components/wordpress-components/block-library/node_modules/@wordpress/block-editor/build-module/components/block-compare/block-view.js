import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';

var BlockView = function BlockView(_ref) {
  var title = _ref.title,
      rawContent = _ref.rawContent,
      renderedContent = _ref.renderedContent,
      action = _ref.action,
      actionText = _ref.actionText,
      className = _ref.className;
  return createElement("div", {
    className: className
  }, createElement("div", {
    className: "block-editor-block-compare__content"
  }, createElement("h2", {
    className: "block-editor-block-compare__heading"
  }, title), createElement("div", {
    className: "block-editor-block-compare__html"
  }, rawContent), createElement("div", {
    className: "block-editor-block-compare__preview edit-post-visual-editor"
  }, renderedContent)), createElement("div", {
    className: "block-editor-block-compare__action"
  }, createElement(Button, {
    isSecondary: true,
    tabIndex: "0",
    onClick: action
  }, actionText)));
};

export default BlockView;
//# sourceMappingURL=block-view.js.map