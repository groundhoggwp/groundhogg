import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { InnerBlocks, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { __experimentalBoxControl as BoxControl } from '@wordpress/components';
var BoxControlVisualizer = BoxControl.__Visualizer;

function GroupEdit(_ref) {
  var _attributes$style, _attributes$style$spa, _attributes$style2, _attributes$style2$vi;

  var attributes = _ref.attributes,
      clientId = _ref.clientId;
  var hasInnerBlocks = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlock = _select.getBlock;

    var block = getBlock(clientId);
    return !!(block && block.innerBlocks.length);
  }, [clientId]);
  var blockWrapperProps = useBlockWrapperProps();
  var _attributes$tagName = attributes.tagName,
      TagName = _attributes$tagName === void 0 ? 'div' : _attributes$tagName;
  return createElement(TagName, blockWrapperProps, createElement(BoxControlVisualizer, {
    values: (_attributes$style = attributes.style) === null || _attributes$style === void 0 ? void 0 : (_attributes$style$spa = _attributes$style.spacing) === null || _attributes$style$spa === void 0 ? void 0 : _attributes$style$spa.padding,
    showValues: (_attributes$style2 = attributes.style) === null || _attributes$style2 === void 0 ? void 0 : (_attributes$style2$vi = _attributes$style2.visualizers) === null || _attributes$style2$vi === void 0 ? void 0 : _attributes$style2$vi.padding
  }), createElement(InnerBlocks, {
    renderAppender: hasInnerBlocks ? undefined : InnerBlocks.ButtonBlockAppender,
    __experimentalTagName: "div",
    __experimentalPassedProps: {
      className: 'wp-block-group__inner-container'
    }
  }));
}

export default GroupEdit;
//# sourceMappingURL=edit.js.map