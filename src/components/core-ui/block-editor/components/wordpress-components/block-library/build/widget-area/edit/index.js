"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = WidgetAreaEdit;

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _coreData = require("@wordpress/core-data");

var _components = require("@wordpress/components");

var _innerBlocks = _interopRequireDefault(require("./inner-blocks"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function WidgetAreaEdit(_ref) {
  var clientId = _ref.clientId,
      className = _ref.className,
      _ref$attributes = _ref.attributes,
      id = _ref$attributes.id,
      name = _ref$attributes.name;
  var isOpen = (0, _data.useSelect)(function (select) {
    return select('core/edit-widgets').getIsWidgetAreaOpen(clientId);
  }, [clientId]);

  var _useDispatch = (0, _data.useDispatch)('core/edit-widgets'),
      setIsWidgetAreaOpen = _useDispatch.setIsWidgetAreaOpen;

  return (0, _element.createElement)(_components.Panel, {
    className: className
  }, (0, _element.createElement)(_components.PanelBody, {
    title: name // This workaround is required to ensure LegacyWidget blocks are not unmounted when the panel is collapsed.
    // Unmounting legacy widgets may have unintended consequences (e.g. TinyMCE not being properly reinitialized)
    ,
    opened: true,
    onToggle: function onToggle() {
      setIsWidgetAreaOpen(clientId, !isOpen);
    },
    className: isOpen ? 'widget-area-is-opened' : ''
  }, (0, _element.createElement)(_coreData.EntityProvider, {
    kind: "root",
    type: "postType",
    id: "widget-area-".concat(id)
  }, (0, _element.createElement)(InnerBlocksContainer, {
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
  return (0, _element.createElement)("div", props, (0, _element.createElement)(_innerBlocks.default, null));
}
//# sourceMappingURL=index.js.map