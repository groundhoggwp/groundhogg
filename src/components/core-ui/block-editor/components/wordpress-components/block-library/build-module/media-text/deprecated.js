import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { noop, isEmpty, omit } from 'lodash';
/**
 * WordPress dependencies
 */

import { InnerBlocks, getColorClassName } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import { imageFillStyles } from './media-container';
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
  return _objectSpread(_objectSpread({}, omit(attributes, ['customBackgroundColor'])), {}, {
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
export default [{
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
    var newRel = isEmpty(rel) ? undefined : rel;

    var _image = createElement("img", {
      src: mediaUrl,
      alt: mediaAlt,
      className: mediaId && mediaType === 'image' ? "wp-image-".concat(mediaId) : null
    });

    if (href) {
      _image = createElement("a", {
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
        return createElement("video", {
          controls: true,
          src: mediaUrl
        });
      }
    };
    var backgroundClass = getColorClassName('background-color', backgroundColor);
    var className = classnames((_classnames = {
      'has-media-on-the-right': 'right' === mediaPosition,
      'has-background': backgroundClass || customBackgroundColor
    }, _defineProperty(_classnames, backgroundClass, backgroundClass), _defineProperty(_classnames, 'is-stacked-on-mobile', isStackedOnMobile), _defineProperty(_classnames, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment), _defineProperty(_classnames, 'is-image-fill', imageFill), _classnames));
    var backgroundStyles = imageFill ? imageFillStyles(mediaUrl, focalPoint) : {};
    var gridTemplateColumns;

    if (mediaWidth !== DEFAULT_MEDIA_WIDTH) {
      gridTemplateColumns = 'right' === mediaPosition ? "auto ".concat(mediaWidth, "%") : "".concat(mediaWidth, "% auto");
    }

    var style = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      gridTemplateColumns: gridTemplateColumns
    };
    return createElement("div", {
      className: className,
      style: style
    }, createElement("figure", {
      className: "wp-block-media-text__media",
      style: backgroundStyles
    }, (mediaTypeRenders[mediaType] || noop)()), createElement("div", {
      className: "wp-block-media-text__content"
    }, createElement(InnerBlocks.Content, null)));
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
        return createElement("img", {
          src: mediaUrl,
          alt: mediaAlt,
          className: mediaId && mediaType === 'image' ? "wp-image-".concat(mediaId) : null
        });
      },
      video: function video() {
        return createElement("video", {
          controls: true,
          src: mediaUrl
        });
      }
    };
    var backgroundClass = getColorClassName('background-color', backgroundColor);
    var className = classnames((_classnames2 = {
      'has-media-on-the-right': 'right' === mediaPosition
    }, _defineProperty(_classnames2, backgroundClass, backgroundClass), _defineProperty(_classnames2, 'is-stacked-on-mobile', isStackedOnMobile), _defineProperty(_classnames2, "is-vertically-aligned-".concat(verticalAlignment), verticalAlignment), _defineProperty(_classnames2, 'is-image-fill', imageFill), _classnames2));
    var backgroundStyles = imageFill ? imageFillStyles(mediaUrl, focalPoint) : {};
    var gridTemplateColumns;

    if (mediaWidth !== DEFAULT_MEDIA_WIDTH) {
      gridTemplateColumns = 'right' === mediaPosition ? "auto ".concat(mediaWidth, "%") : "".concat(mediaWidth, "% auto");
    }

    var style = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      gridTemplateColumns: gridTemplateColumns
    };
    return createElement("div", {
      className: className,
      style: style
    }, createElement("figure", {
      className: "wp-block-media-text__media",
      style: backgroundStyles
    }, (mediaTypeRenders[mediaType] || noop)()), createElement("div", {
      className: "wp-block-media-text__content"
    }, createElement(InnerBlocks.Content, null)));
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
        return createElement("img", {
          src: mediaUrl,
          alt: mediaAlt
        });
      },
      video: function video() {
        return createElement("video", {
          controls: true,
          src: mediaUrl
        });
      }
    };
    var backgroundClass = getColorClassName('background-color', backgroundColor);
    var className = classnames((_classnames3 = {
      'has-media-on-the-right': 'right' === mediaPosition
    }, _defineProperty(_classnames3, backgroundClass, backgroundClass), _defineProperty(_classnames3, 'is-stacked-on-mobile', isStackedOnMobile), _classnames3));
    var gridTemplateColumns;

    if (mediaWidth !== DEFAULT_MEDIA_WIDTH) {
      gridTemplateColumns = 'right' === mediaPosition ? "auto ".concat(mediaWidth, "%") : "".concat(mediaWidth, "% auto");
    }

    var style = {
      backgroundColor: backgroundClass ? undefined : customBackgroundColor,
      gridTemplateColumns: gridTemplateColumns
    };
    return createElement("div", {
      className: className,
      style: style
    }, createElement("figure", {
      className: "wp-block-media-text__media"
    }, (mediaTypeRenders[mediaType] || noop)()), createElement("div", {
      className: "wp-block-media-text__content"
    }, createElement(InnerBlocks.Content, null)));
  }
}];
//# sourceMappingURL=deprecated.js.map