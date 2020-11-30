"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames4 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _blockEditor = require("@wordpress/block-editor");

var _mediaContainer = require("./media-container");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var DEFAULT_MEDIA_WIDTH = 50;

var migrateCustomColors = function migrateCustomColors(attributes) {
  if (!attributes.customBackgroundColor) {
    return attributes;
  }

  var style = {
    color: {
      background: attributes.customBackgroundColor
    }
  };
  return _objectSpread(_objectSpread({}, (0, _lodash.omit)(attributes, ['customBackgroundColor'])), {}, {
    style: style
  });
};

var baseAttributes = {
  align: {
    type: 'string',
    default: 'wide'
  },
  backgroundColor: {
    type: 'string'
  },
  mediaAlt: {
    type: 'string',
    source: 'attribute',
    selector: 'figure img',
    attribute: 'alt',
    default: ''
  },
  mediaPosition: {
    type: 'string',
    default: 'left'
  },
  mediaId: {
    type: 'number'
  },
  mediaType: {
    type: 'string'
  },
  mediaWidth: {
    type: 'number',
    default: 50
  },
  isStackedOnMobile: {
    type: 'boolean',
    default: false
  }
};
var _default = [{
  attributes: _objectSpread(_objectSpread({}, baseAttributes), {}, {
    customBackgroundColor: {
      type: 'string'
    },
    mediaLink: {
      type: 'string'
    },
    linkDestination: {
      type: 'string'
    },
    linkTarget: {
      type: 'string',
      source: 'attribute',
      selector: 'figure a',
      attribute: 'target'
    },
    href: {
      type: 'string',
      source: 'attribute',
      selector: 'figure a',
      attribute: 'href'
    },
    rel: {
      type: 'string',
      source: 'attribute',
      selector: 'figure a',
      attribute: 'rel'
    },
    linkClass: {
      type: 'string',
      source: 'attribute',
      selector: 'figure a',
      attribute: 'class'
    },
    verticalAlignment: {
      type: 'string'
    },
    imageFill: {
      type: 'boolean'
    },
    focalPoint: {
      type: 'object'
    }
  }),
  migrate: migrateCustomColors,
  save: function save(_ref) {
    var _classnames;

    var attributes = _ref.attributes;
    var backgroundColor = attributes.backgroundColor,
        customBackgroundColor = attributes.customBackgroundColor,
        isStackedOnMobile = attributes.isStackedOnMobile,
        mediaAlt = attributes.mediaAlt,
        mediaPosition = attributes.mediaPosition,
        mediaType = attributes.mediaType,
        mediaUrl = attributes.mediaUrl,
        mediaWidth = attributes.mediaWidth,
        mediaId = attributes.mediaId,
        verticalAlignment = attributes.verticalAlignment,
        imageFill = attributes.imageFill,
        focalPoint = attributes.focalPoint,
        linkClass = attributes.linkClass,
        href = attributes.href,
        linkTarget = attributes.linkTarget,
        rel = attributes.rel;
    var newRel = (0, _lodash.isEmpty)(rel) ? undefined : rel;

    var _image = (0, _element.createElement)("img", {
      src: mediaUrl,
      alt: mediaAlt,
      className: mediaId && mediaType === 'image' ? "wp-image-".concat(mediaId) : null
    });

    if (href) {
      _image = (0, _element.createElement)("a", {
        className: linkClass,
        href: href,
        target: linkTarget,
        rel: newRel
      }, _image);
    }

    var mediaTypeRenders = {
      image: function image() {
        return _image;
      },
      video: function video() {
        return (0, _element.createElement)("video", {
          controls: true,
          src: mediaUrl
        });
      }
    };
    var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', backgroundColor);
    var className = (0, _classnames4.default)((_classnames = {
      'has-media-on-the-right': 'right' === mediaPosition,
      'has-background': backgroundClass || customBackgroundColor
    }, (0, _defineProperty2.default)(_classnames, backgroundClass, backgroundClass), (0, _defineProperty2.default)(_classnames, 'is-stacked-on-mobile', isStackedOnMobile), (0, _defineProperty2.default)(_classnames, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment), (0, _defineProperty2.default)(_classnames, 'is-image-fill', imageFill), _classnames));
    var backgroundStyles = imageFill ? (0, _mediaContainer.imageFillStyles)(mediaUrl, focalPoint) : {};
    var gridTemplateColumns;

    if (mediaWidth !== DEFAULT_MEDIA_WIDTH) {
      gridTemplateColumns = 'right' === mediaPosition ? "auto ".concat(mediaWidth, "%") : "".concat(mediaWidth, "% auto");
    }

    var style = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      gridTemplateColumns: gridTemplateColumns
    };
    return (0, _element.createElement)("div", {
      className: className,
      style: style
    }, (0, _element.createElement)("figure", {
      className: "wp-block-media-text__media",
      style: backgroundStyles
    }, (mediaTypeRenders[mediaType] || _lodash.noop)()), (0, _element.createElement)("div", {
      className: "wp-block-media-text__content"
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null)));
  }
}, {
  attributes: _objectSpread(_objectSpread({}, baseAttributes), {}, {
    customBackgroundColor: {
      type: 'string'
    },
    mediaUrl: {
      type: 'string',
      source: 'attribute',
      selector: 'figure video,figure img',
      attribute: 'src'
    },
    verticalAlignment: {
      type: 'string'
    },
    imageFill: {
      type: 'boolean'
    },
    focalPoint: {
      type: 'object'
    }
  }),
  migrate: migrateCustomColors,
  save: function save(_ref2) {
    var _classnames2;

    var attributes = _ref2.attributes;
    var backgroundColor = attributes.backgroundColor,
        customBackgroundColor = attributes.customBackgroundColor,
        isStackedOnMobile = attributes.isStackedOnMobile,
        mediaAlt = attributes.mediaAlt,
        mediaPosition = attributes.mediaPosition,
        mediaType = attributes.mediaType,
        mediaUrl = attributes.mediaUrl,
        mediaWidth = attributes.mediaWidth,
        mediaId = attributes.mediaId,
        verticalAlignment = attributes.verticalAlignment,
        imageFill = attributes.imageFill,
        focalPoint = attributes.focalPoint;
    var mediaTypeRenders = {
      image: function image() {
        return (0, _element.createElement)("img", {
          src: mediaUrl,
          alt: mediaAlt,
          className: mediaId && mediaType === 'image' ? "wp-image-".concat(mediaId) : null
        });
      },
      video: function video() {
        return (0, _element.createElement)("video", {
          controls: true,
          src: mediaUrl
        });
      }
    };
    var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', backgroundColor);
    var className = (0, _classnames4.default)((_classnames2 = {
      'has-media-on-the-right': 'right' === mediaPosition
    }, (0, _defineProperty2.default)(_classnames2, backgroundClass, backgroundClass), (0, _defineProperty2.default)(_classnames2, 'is-stacked-on-mobile', isStackedOnMobile), (0, _defineProperty2.default)(_classnames2, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment), (0, _defineProperty2.default)(_classnames2, 'is-image-fill', imageFill), _classnames2));
    var backgroundStyles = imageFill ? (0, _mediaContainer.imageFillStyles)(mediaUrl, focalPoint) : {};
    var gridTemplateColumns;

    if (mediaWidth !== DEFAULT_MEDIA_WIDTH) {
      gridTemplateColumns = 'right' === mediaPosition ? "auto ".concat(mediaWidth, "%") : "".concat(mediaWidth, "% auto");
    }

    var style = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      gridTemplateColumns: gridTemplateColumns
    };
    return (0, _element.createElement)("div", {
      className: className,
      style: style
    }, (0, _element.createElement)("figure", {
      className: "wp-block-media-text__media",
      style: backgroundStyles
    }, (mediaTypeRenders[mediaType] || _lodash.noop)()), (0, _element.createElement)("div", {
      className: "wp-block-media-text__content"
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null)));
  }
}, {
  attributes: _objectSpread(_objectSpread({}, baseAttributes), {}, {
    customBackgroundColor: {
      type: 'string'
    },
    mediaUrl: {
      type: 'string',
      source: 'attribute',
      selector: 'figure video,figure img',
      attribute: 'src'
    }
  }),
  save: function save(_ref3) {
    var _classnames3;

    var attributes = _ref3.attributes;
    var backgroundColor = attributes.backgroundColor,
        customBackgroundColor = attributes.customBackgroundColor,
        isStackedOnMobile = attributes.isStackedOnMobile,
        mediaAlt = attributes.mediaAlt,
        mediaPosition = attributes.mediaPosition,
        mediaType = attributes.mediaType,
        mediaUrl = attributes.mediaUrl,
        mediaWidth = attributes.mediaWidth;
    var mediaTypeRenders = {
      image: function image() {
        return (0, _element.createElement)("img", {
          src: mediaUrl,
          alt: mediaAlt
        });
      },
      video: function video() {
        return (0, _element.createElement)("video", {
          controls: true,
          src: mediaUrl
        });
      }
    };
    var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', backgroundColor);
    var className = (0, _classnames4.default)((_classnames3 = {
      'has-media-on-the-right': 'right' === mediaPosition
    }, (0, _defineProperty2.default)(_classnames3, backgroundClass, backgroundClass), (0, _defineProperty2.default)(_classnames3, 'is-stacked-on-mobile', isStackedOnMobile), _classnames3));
    var gridTemplateColumns;

    if (mediaWidth !== DEFAULT_MEDIA_WIDTH) {
      gridTemplateColumns = 'right' === mediaPosition ? "auto ".concat(mediaWidth, "%") : "".concat(mediaWidth, "% auto");
    }

    var style = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      gridTemplateColumns: gridTemplateColumns
    };
    return (0, _element.createElement)("div", {
      className: className,
      style: style
    }, (0, _element.createElement)("figure", {
      className: "wp-block-media-text__media"
    }, (mediaTypeRenders[mediaType] || _lodash.noop)()), (0, _element.createElement)("div", {
      className: "wp-block-media-text__content"
    }, (0, _element.createElement)(_blockEditor.InnerBlocks.Content, null)));
  }
}];
exports.default = _default;
//# sourceMappingURL=deprecated.js.map