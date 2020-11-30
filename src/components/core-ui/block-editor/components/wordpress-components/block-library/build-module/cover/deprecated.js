import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { omit } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { createBlock } from '@wordpress/blocks';
import { RichText, getColorClassName, InnerBlocks, __experimentalGetGradientClass } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import { IMAGE_BACKGROUND_TYPE, VIDEO_BACKGROUND_TYPE, backgroundImageStyles, dimRatioToClass } from './shared';
var blockAttributes = {
  url: {
    type: 'string'
  },
  id: {
    type: 'number'
  },
  hasParallax: {
    type: 'boolean',
    default: false
  },
  dimRatio: {
    type: 'number',
    default: 50
  },
  overlayColor: {
    type: 'string'
  },
  customOverlayColor: {
    type: 'string'
  },
  backgroundType: {
    type: 'string',
    default: 'image'
  },
  focalPoint: {
    type: 'object'
  }
};
var deprecated = [{
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    title: {
      type: 'string',
      source: 'html',
      selector: 'p'
    },
    contentAlign: {
      type: 'string',
      default: 'center'
    },
    minHeight: {
      type: 'number'
    },
    gradient: {
      type: 'string'
    },
    customGradient: {
      type: 'string'
    }
  }),
  save: function save(_ref) {
    var attributes = _ref.attributes;
    var backgroundType = attributes.backgroundType,
        gradient = attributes.gradient,
        customGradient = attributes.customGradient,
        customOverlayColor = attributes.customOverlayColor,
        dimRatio = attributes.dimRatio,
        focalPoint = attributes.focalPoint,
        hasParallax = attributes.hasParallax,
        overlayColor = attributes.overlayColor,
        url = attributes.url,
        minHeight = attributes.minHeight;
    var overlayColorClass = getColorClassName('background-color', overlayColor);

    var gradientClass = __experimentalGetGradientClass(gradient);

    var style = backgroundType === IMAGE_BACKGROUND_TYPE ? backgroundImageStyles(url) : {};

    if (!overlayColorClass) {
      style.backgroundColor = customOverlayColor;
    }

    if (focalPoint && !hasParallax) {
      style.backgroundPosition = "".concat(Math.round(focalPoint.x * 100), "% ").concat(Math.round(focalPoint.y * 100), "%");
    }

    if (customGradient && !url) {
      style.background = customGradient;
    }

    style.minHeight = minHeight || undefined;
    var classes = classnames(dimRatioToClass(dimRatio), overlayColorClass, _defineProperty({
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax,
      'has-background-gradient': customGradient
    }, gradientClass, !url && gradientClass));
    return createElement("div", {
      className: classes,
      style: style
    }, url && (gradient || customGradient) && dimRatio !== 0 && createElement("span", {
      "aria-hidden": "true",
      className: classnames('wp-block-cover__gradient-background', gradientClass),
      style: customGradient ? {
        background: customGradient
      } : undefined
    }), VIDEO_BACKGROUND_TYPE === backgroundType && url && createElement("video", {
      className: "wp-block-cover__video-background",
      autoPlay: true,
      muted: true,
      loop: true,
      src: url
    }), createElement("div", {
      className: "wp-block-cover__inner-container"
    }, createElement(InnerBlocks.Content, null)));
  }
}, {
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    title: {
      type: 'string',
      source: 'html',
      selector: 'p'
    },
    contentAlign: {
      type: 'string',
      default: 'center'
    },
    minHeight: {
      type: 'number'
    },
    gradient: {
      type: 'string'
    },
    customGradient: {
      type: 'string'
    }
  }),
  save: function save(_ref2) {
    var attributes = _ref2.attributes;
    var backgroundType = attributes.backgroundType,
        gradient = attributes.gradient,
        customGradient = attributes.customGradient,
        customOverlayColor = attributes.customOverlayColor,
        dimRatio = attributes.dimRatio,
        focalPoint = attributes.focalPoint,
        hasParallax = attributes.hasParallax,
        overlayColor = attributes.overlayColor,
        url = attributes.url,
        minHeight = attributes.minHeight;
    var overlayColorClass = getColorClassName('background-color', overlayColor);

    var gradientClass = __experimentalGetGradientClass(gradient);

    var style = backgroundType === IMAGE_BACKGROUND_TYPE ? backgroundImageStyles(url) : {};

    if (!overlayColorClass) {
      style.backgroundColor = customOverlayColor;
    }

    if (focalPoint && !hasParallax) {
      style.backgroundPosition = "".concat(focalPoint.x * 100, "% ").concat(focalPoint.y * 100, "%");
    }

    if (customGradient && !url) {
      style.background = customGradient;
    }

    style.minHeight = minHeight || undefined;
    var classes = classnames(dimRatioToClass(dimRatio), overlayColorClass, _defineProperty({
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax,
      'has-background-gradient': customGradient
    }, gradientClass, !url && gradientClass));
    return createElement("div", {
      className: classes,
      style: style
    }, url && (gradient || customGradient) && dimRatio !== 0 && createElement("span", {
      "aria-hidden": "true",
      className: classnames('wp-block-cover__gradient-background', gradientClass),
      style: customGradient ? {
        background: customGradient
      } : undefined
    }), VIDEO_BACKGROUND_TYPE === backgroundType && url && createElement("video", {
      className: "wp-block-cover__video-background",
      autoPlay: true,
      muted: true,
      loop: true,
      src: url
    }), createElement("div", {
      className: "wp-block-cover__inner-container"
    }, createElement(InnerBlocks.Content, null)));
  }
}, {
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    title: {
      type: 'string',
      source: 'html',
      selector: 'p'
    },
    contentAlign: {
      type: 'string',
      default: 'center'
    }
  }),
  supports: {
    align: true
  },
  save: function save(_ref3) {
    var attributes = _ref3.attributes;
    var backgroundType = attributes.backgroundType,
        contentAlign = attributes.contentAlign,
        customOverlayColor = attributes.customOverlayColor,
        dimRatio = attributes.dimRatio,
        focalPoint = attributes.focalPoint,
        hasParallax = attributes.hasParallax,
        overlayColor = attributes.overlayColor,
        title = attributes.title,
        url = attributes.url;
    var overlayColorClass = getColorClassName('background-color', overlayColor);
    var style = backgroundType === IMAGE_BACKGROUND_TYPE ? backgroundImageStyles(url) : {};

    if (!overlayColorClass) {
      style.backgroundColor = customOverlayColor;
    }

    if (focalPoint && !hasParallax) {
      style.backgroundPosition = "".concat(focalPoint.x * 100, "% ").concat(focalPoint.y * 100, "%");
    }

    var classes = classnames(dimRatioToClass(dimRatio), overlayColorClass, _defineProperty({
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax
    }, "has-".concat(contentAlign, "-content"), contentAlign !== 'center'));
    return createElement("div", {
      className: classes,
      style: style
    }, VIDEO_BACKGROUND_TYPE === backgroundType && url && createElement("video", {
      className: "wp-block-cover__video-background",
      autoPlay: true,
      muted: true,
      loop: true,
      src: url
    }), !RichText.isEmpty(title) && createElement(RichText.Content, {
      tagName: "p",
      className: "wp-block-cover-text",
      value: title
    }));
  },
  migrate: function migrate(attributes) {
    return [omit(attributes, ['title', 'contentAlign']), [createBlock('core/paragraph', {
      content: attributes.title,
      align: attributes.contentAlign,
      fontSize: 'large',
      placeholder: __('Write title…')
    })]];
  }
}, {
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    title: {
      type: 'string',
      source: 'html',
      selector: 'p'
    },
    contentAlign: {
      type: 'string',
      default: 'center'
    },
    align: {
      type: 'string'
    }
  }),
  supports: {
    className: false
  },
  save: function save(_ref4) {
    var attributes = _ref4.attributes;
    var url = attributes.url,
        title = attributes.title,
        hasParallax = attributes.hasParallax,
        dimRatio = attributes.dimRatio,
        align = attributes.align,
        contentAlign = attributes.contentAlign,
        overlayColor = attributes.overlayColor,
        customOverlayColor = attributes.customOverlayColor;
    var overlayColorClass = getColorClassName('background-color', overlayColor);
    var style = backgroundImageStyles(url);

    if (!overlayColorClass) {
      style.backgroundColor = customOverlayColor;
    }

    var classes = classnames('wp-block-cover-image', dimRatioToClass(dimRatio), overlayColorClass, _defineProperty({
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax
    }, "has-".concat(contentAlign, "-content"), contentAlign !== 'center'), align ? "align".concat(align) : null);
    return createElement("div", {
      className: classes,
      style: style
    }, !RichText.isEmpty(title) && createElement(RichText.Content, {
      tagName: "p",
      className: "wp-block-cover-image-text",
      value: title
    }));
  },
  migrate: function migrate(attributes) {
    return [omit(attributes, ['title', 'contentAlign', 'align']), [createBlock('core/paragraph', {
      content: attributes.title,
      align: attributes.contentAlign,
      fontSize: 'large',
      placeholder: __('Write title…')
    })]];
  }
}, {
  attributes: _objectSpread(_objectSpread({}, blockAttributes), {}, {
    title: {
      type: 'string',
      source: 'html',
      selector: 'h2'
    },
    align: {
      type: 'string'
    },
    contentAlign: {
      type: 'string',
      default: 'center'
    }
  }),
  supports: {
    className: false
  },
  save: function save(_ref5) {
    var attributes = _ref5.attributes;
    var url = attributes.url,
        title = attributes.title,
        hasParallax = attributes.hasParallax,
        dimRatio = attributes.dimRatio,
        align = attributes.align;
    var style = backgroundImageStyles(url);
    var classes = classnames('wp-block-cover-image', dimRatioToClass(dimRatio), {
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax
    }, align ? "align".concat(align) : null);
    return createElement("section", {
      className: classes,
      style: style
    }, createElement(RichText.Content, {
      tagName: "h2",
      value: title
    }));
  },
  migrate: function migrate(attributes) {
    return [omit(attributes, ['title', 'contentAlign', 'align']), [createBlock('core/paragraph', {
      content: attributes.title,
      align: attributes.contentAlign,
      fontSize: 'large',
      placeholder: __('Write title…')
    })]];
  }
}];
export default deprecated;
//# sourceMappingURL=deprecated.js.map