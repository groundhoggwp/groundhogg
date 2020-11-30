import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { EntityProvider } from '@wordpress/core-data';
import { Panel, PanelBody } from '@wordpress/components';
/**
 * Internal dependencies
 */

import WidgetAreaInnerBlocks from './inner-blocks';
export default function WidgetAreaEdit(_ref) {
  var clientId = _ref.clientId,
      className = _ref.className,
      _ref$attributes = _ref.attributes,
      id = _ref$attributes.id,
      name = _ref$attributes.name;
  var isOpen = useSelect(function (select) {
    return select('core/edit-widgets').getIsWidgetAreaOpen(clientId);
  }, [clientId]);

  var _useDispatch = useDispatch('core/edit-widgets'),
      setIsWidgetAreaOpen = _useDispatch.setIsWidgetAreaOpen;

  return createElement(Panel, {
    className: className
  }, createElement(PanelBody, {
    title: name // This workaround is required to ensure LegacyWidget blocks are not unmounted when the panel is collapsed.
    // Unmounting legacy widgets may have unintended consequences (e.g. TinyMCE not being properly reinitialized)
    ,
    opened: true,
    onToggle: function onToggle() {
      setIsWidgetAreaOpen(clientId, !isOpen);
    },
    className: isOpen ? 'widget-area-is-opened' : ''
  }, createElement(EntityProvider, {
    kind: "root",
    type: "postType",
    id: "widget-area-".concat(id)
  }, createElement(InnerBlocksContainer, {
    isOpen: isOpen
  }))));
}

function InnerBlocksContainer(_ref2) {
  var isOpen = _ref2.isOpen;
  var props = isOpen ? {} : {
    hidden: true,
    'aria-hidden': true,
    style: {
      display: 'none'
    }
  };
  return createElement("div", props, createElement(WidgetAreaInnerBlocks, null));
}
//# sourceMappingURL=index.js.map