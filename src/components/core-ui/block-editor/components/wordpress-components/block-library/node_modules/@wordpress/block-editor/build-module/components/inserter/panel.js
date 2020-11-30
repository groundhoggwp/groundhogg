import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Icon } from '@wordpress/components';

function InserterPanel(_ref) {
  var title = _ref.title,
      icon = _ref.icon,
      children = _ref.children;
  return createElement(Fragment, null, createElement("div", {
    className: "block-editor-inserter__panel-header"
  }, createElement("h2", {
    className: "block-editor-inserter__panel-title"
  }, title), createElement(Icon, {
    icon: icon
  })), createElement("div", {
    className: "block-editor-inserter__panel-content"
  }, children));
}

export default InserterPanel;
//# sourceMappingURL=panel.js.map