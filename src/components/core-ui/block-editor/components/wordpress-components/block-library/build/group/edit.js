"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var BoxControlVisualizer = _components.__experimentalBoxControl.__Visualizer;

function GroupEdit(_ref) {
  var _attributes$style, _attributes$style$spa, _attributes$style2, _attributes$style2$vi;

  var attributes = _ref.attributes,
      clientId = _ref.clientId;
  var hasInnerBlocks = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlock = _select.getBlock;

    var block = getBlock(clientId);
    return !!(block && block.innerBlocks.length);
  }, [clientId]);
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();
  var _attributes$tagName = attributes.tagName,
      TagName = _attributes$tagName === void 0 ? 'div' : _attributes$tagName;
  return (0, _element.createElement)(TagName, blockWrapperProps, (0, _element.createElement)(BoxControlVisualizer, {
    values: (_attributes$style = attributes.style) === null || _attributes$style === void 0 ? void 0 : (_attributes$style$spa = _attributes$style.spacing) === null || _attributes$style$spa === void 0 ? void 0 : _attributes$style$spa.padding,
    showValues: (_attributes$style2 = attributes.style) === null || _attributes$style2 === void 0 ? void 0 : (_attributes$style2$vi = _attributes$style2.visualizers) === null || _attributes$style2$vi === void 0 ? void 0 : _attributes$style2$vi.padding
  }), (0, _element.createElement)(_blockEditor.InnerBlocks, {
    renderAppender: hasInnerBlocks ? undefined : _blockEditor.InnerBlocks.ButtonBlockAppender,
    __experimentalTagName: "div",
    __experimentalPassedProps: {
      className: 'wp-block-group__inner-container'
    }
  }));
}

var _default = GroupEdit;
exports.default = _default;
//# sourceMappingURL=edit.js.map