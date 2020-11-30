import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { cloneElement, Children } from '@wordpress/element';
import { useResizeObserver } from '@wordpress/compose';

function ResponsiveWrapper(_ref) {
  var naturalWidth = _ref.naturalWidth,
      naturalHeight = _ref.naturalHeight,
      children = _ref.children,
      _ref$isInline = _ref.isInline,
      isInline = _ref$isInline === void 0 ? false : _ref$isInline;

  var _useResizeObserver = useResizeObserver(),
      _useResizeObserver2 = _slicedToArray(_useResizeObserver, 2),
      containerResizeListener = _useResizeObserver2[0],
      containerWidth = _useResizeObserver2[1].width;

  if (Children.count(children) !== 1) {
    return null;
  }

  var imageStyle = {
    paddingBottom: naturalWidth < containerWidth ? naturalHeight : naturalHeight / naturalWidth * 100 + '%'
  };
  var TagName = isInline ? 'span' : 'div';
  return createElement(TagName, {
    className: "components-responsive-wrapper"
  }, containerResizeListener, createElement(TagName, {
    style: imageStyle
  }), cloneElement(children, {
    className: classnames('components-responsive-wrapper__content', children.props.className)
  }));
}

export default ResponsiveWrapper;
//# sourceMappingURL=index.js.map