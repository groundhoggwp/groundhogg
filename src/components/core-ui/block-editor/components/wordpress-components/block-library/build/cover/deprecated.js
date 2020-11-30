"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _classnames5 = _interopRequireDefault(require("classnames"));

var _blocks = require("@wordpress/blocks");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _shared = require("./shared");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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
    var overlayColorClass = (0, _blockEditor.getColorClassName)('background-color', overlayColor);
    var gradientClass = (0, _blockEditor.__experimentalGetGradientClass)(gradient);
    var style = backgroundType === _shared.IMAGE_BACKGROUND_TYPE ? (0, _shared.backgroundImageStyles)(url) : {};

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
    var classes = (0, _classnames5.default)((0, _shared.dimRatioToClass)(dimRatio), overlayColorClass, (0, _defineProperty2.default)({
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax,
      'has-background-gradient': customGradient
    }, gradientClass, !url && gradientClass));
    return (0, _element.createElement)("div", {
      className: classes,
      style: style
    }, url && (gradient || customGradient) && dimRatio !== 0 && (0, _element.createElement)("span", {
      "aria-hidden": "true",
      className: (0, _classnames5.default)('wp-block-cover__gradient-background', gradientClass),
      style: customGradient ? {
        background: customGradient
      } : undefined
    }), _shared.VIDEO_BACKGROUND_TYPE === backgroundType && url && (0, _element.createElement)("video", {
      className: "wp-block-cover__video-background",
      autoPlay: true,
      muted: true,
      loop: true,
      src: url
    }), (0, _element.createElement)("div", {
      className: "wp-block-cover__inner-container"
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null)));
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
    var overlayColorClass = (0, _blockEditor.getColorClassName)('background-color', overlayColor);
    var gradientClass = (0, _blockEditor.__experimentalGetGradientClass)(gradient);
    var style = backgroundType === _shared.IMAGE_BACKGROUND_TYPE ? (0, _shared.backgroundImageStyles)(url) : {};

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
    var classes = (0, _classnames5.default)((0, _shared.dimRatioToClass)(dimRatio), overlayColorClass, (0, _defineProperty2.default)({
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax,
      'has-background-gradient': customGradient
    }, gradientClass, !url && gradientClass));
    return (0, _element.createElement)("div", {
      className: classes,
      style: style
    }, url && (gradient || customGradient) && dimRatio !== 0 && (0, _element.createElement)("span", {
      "aria-hidden": "true",
      className: (0, _classnames5.default)('wp-block-cover__gradient-background', gradientClass),
      style: customGradient ? {
        background: customGradient
      } : undefined
    }), _shared.VIDEO_BACKGROUND_TYPE === backgroundType && url && (0, _element.createElement)("video", {
      className: "wp-block-cover__video-background",
      autoPlay: true,
      muted: true,
      loop: true,
      src: url
    }), (0, _element.createElement)("div", {
      className: "wp-block-cover__inner-container"
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null)));
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
    var overlayColorClass = (0, _blockEditor.getColorClassName)('background-color', overlayColor);
    var style = backgroundType === _shared.IMAGE_BACKGROUND_TYPE ? (0, _shared.backgroundImageStyles)(url) : {};

    if (!overlayColorClass) {
      style.backgroundColor = customOverlayColor;
    }

    if (focalPoint && !hasParallax) {
      style.backgroundPosition = "".concat(focalPoint.x * 100, "% ").concat(focalPoint.y * 100, "%");
    }

    var classes = (0, _classnames5.default)((0, _shared.dimRatioToClass)(dimRatio), overlayColorClass, (0, _defineProperty2.default)({
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax
    }, "has-".concat(contentAlign, "-content"), contentAlign !== 'center'));
    return (0, _element.createElement)("div", {
      className: classes,
      style: style
    }, _shared.VIDEO_BACKGROUND_TYPE === backgroundType && url && (0, _element.createElement)("video", {
      className: "wp-block-cover__video-background",
      autoPlay: true,
      muted: true,
      loop: true,
      src: url
    }), !_blockEditor.RichText.isEmpty(title) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "p",
      className: "wp-block-cover-text",
      value: title
    }));
  },
  migrate: function migrate(attributes) {
    return [(0, _lodash.omit)(attributes, ['title', 'contentAlign']), [(0, _blocks.createBlock)('core/paragraph', {
      content: attributes.title,
      align: attributes.contentAlign,
      fontSize: 'large',
      placeholder: (0, _i18n.__)('Write title…')
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
    var overlayColorClass = (0, _blockEditor.getColorClassName)('background-color', overlayColor);
    var style = (0, _shared.backgroundImageStyles)(url);

    if (!overlayColorClass) {
      style.backgroundColor = customOverlayColor;
    }

    var classes = (0, _classnames5.default)('wp-block-cover-image', (0, _shared.dimRatioToClass)(dimRatio), overlayColorClass, (0, _defineProperty2.default)({
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax
    }, "has-".concat(contentAlign, "-content"), contentAlign !== 'center'), align ? "align".concat(align) : null);
    return (0, _element.createElement)("div", {
      className: classes,
      style: style
    }, !_blockEditor.RichText.isEmpty(title) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "p",
      className: "wp-block-cover-image-text",
      value: title
    }));
  },
  migrate: function migrate(attributes) {
    return [(0, _lodash.omit)(attributes, ['title', 'contentAlign', 'align']), [(0, _blocks.createBlock)('core/paragraph', {
      content: attributes.title,
      align: attributes.contentAlign,
      fontSize: 'large',
      placeholder: (0, _i18n.__)('Write title…')
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
    var style = (0, _shared.backgroundImageStyles)(url);
    var classes = (0, _classnames5.default)('wp-block-cover-image', (0, _shared.dimRatioToClass)(dimRatio), {
      'has-background-dim': dimRatio !== 0,
      'has-parallax': hasParallax
    }, align ? "align".concat(align) : null);
    return (0, _element.createElement)("section", {
      className: classes,
      style: style
    }, (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "h2",
      value: title
    }));
  },
  migrate: function migrate(attributes) {
    return [(0, _lodash.omit)(attributes, ['title', 'contentAlign', 'align']), [(0, _blocks.createBlock)('core/paragraph', {
      content: attributes.title,
      align: attributes.contentAlign,
      fontSize: 'large',
      placeholder: (0, _i18n.__)('Write title…')
    })]];
  }
}];
var _default = deprecated;
exports.default = _default;
//# sourceMappingURL=deprecated.js.map