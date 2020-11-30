import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { map, some } from 'lodash';
/**
 * WordPress dependencies
 */

import { RichText } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import { defaultColumnsNumber } from './shared';
var deprecated = [{
  attributes: {
    images: {
      type: 'array',
      default: [],
      source: 'query',
      selector: '.blocks-gallery-item',
      query: {
        url: {
          type: 'string',
          source: 'attribute',
          selector: 'img',
          attribute: 'src'
        },
        fullUrl: {
          type: 'string',
          source: 'attribute',
          selector: 'img',
          attribute: 'data-full-url'
        },
        link: {
          type: 'string',
          source: 'attribute',
          selector: 'img',
          attribute: 'data-link'
        },
        alt: {
          type: 'string',
          source: 'attribute',
          selector: 'img',
          attribute: 'alt',
          default: ''
        },
        id: {
          type: 'string',
          source: 'attribute',
          selector: 'img',
          attribute: 'data-id'
        },
        caption: {
          type: 'string',
          source: 'html',
          selector: '.blocks-gallery-item__caption'
        }
      }
    },
    ids: {
      type: 'array',
      items: {
        type: 'number'
      },
      default: []
    },
    columns: {
      type: 'number',
      minimum: 1,
      maximum: 8
    },
    caption: {
      type: 'string',
      source: 'html',
      selector: '.blocks-gallery-caption'
    },
    imageCrop: {
      type: 'boolean',
      default: true
    },
    linkTo: {
      type: 'string',
      default: 'none'
    },
    sizeSlug: {
      type: 'string',
      default: 'large'
    }
  },
  supports: {
    align: true
  },
  isEligible: function isEligible(_ref) {
    var linkTo = _ref.linkTo;
    return !linkTo || linkTo === 'attachment' || linkTo === 'media';
  },
  migrate: function migrate(attributes) {
    var linkTo = attributes.linkTo;

    if (!attributes.linkTo) {
      linkTo = 'none';
    } else if (attributes.linkTo === 'attachment') {
      linkTo = 'post';
    } else if (attributes.linkTo === 'media') {
      linkTo = 'file';
    }

    return _objectSpread(_objectSpread({}, attributes), {}, {
      linkTo: linkTo
    });
  },
  save: function save(_ref2) {
    var attributes = _ref2.attributes;
    var images = attributes.images,
        _attributes$columns = attributes.columns,
        columns = _attributes$columns === void 0 ? defaultColumnsNumber(attributes) : _attributes$columns,
        imageCrop = attributes.imageCrop,
        caption = attributes.caption,
        linkTo = attributes.linkTo;
    return createElement("figure", {
      className: "columns-".concat(columns, " ").concat(imageCrop ? 'is-cropped' : '')
    }, createElement("ul", {
      className: "blocks-gallery-grid"
    }, images.map(function (image) {
      var href;

      switch (linkTo) {
        case 'media':
          href = image.fullUrl || image.url;
          break;

        case 'attachment':
          href = image.link;
          break;
      }

      var img = createElement("img", {
        src: image.url,
        alt: image.alt,
        "data-id": image.id,
        "data-full-url": image.fullUrl,
        "data-link": image.link,
        className: image.id ? "wp-image-".concat(image.id) : null
      });
      return createElement("li", {
        key: image.id || image.url,
        className: "blocks-gallery-item"
      }, createElement("figure", null, href ? createElement("a", {
        href: href
      }, img) : img, !RichText.isEmpty(image.caption) && createElement(RichText.Content, {
        tagName: "figcaption",
        className: "blocks-gallery-item__caption",
        value: image.caption
      })));
    })), !RichText.isEmpty(caption) && createElement(RichText.Content, {
      tagName: "figcaption",
      className: "blocks-gallery-caption",
      value: caption
    }));
  }
}, {
  attributes: {
    images: {
      type: 'array',
      default: [],
      source: 'query',
      selector: '.blocks-gallery-item',
      query: {
        url: {
          source: 'attribute',
          selector: 'img',
          attribute: 'src'
        },
        fullUrl: {
          source: 'attribute',
          selector: 'img',
          attribute: 'data-full-url'
        },
        link: {
          source: 'attribute',
          selector: 'img',
          attribute: 'data-link'
        },
        alt: {
          source: 'attribute',
          selector: 'img',
          attribute: 'alt',
          default: ''
        },
        id: {
          source: 'attribute',
          selector: 'img',
          attribute: 'data-id'
        },
        caption: {
          type: 'string',
          source: 'html',
          selector: '.blocks-gallery-item__caption'
        }
      }
    },
    ids: {
      type: 'array',
      default: []
    },
    columns: {
      type: 'number'
    },
    caption: {
      type: 'string',
      source: 'html',
      selector: '.blocks-gallery-caption'
    },
    imageCrop: {
      type: 'boolean',
      default: true
    },
    linkTo: {
      type: 'string',
      default: 'none'
    }
  },
  supports: {
    align: true
  },
  isEligible: function isEligible(_ref3) {
    var ids = _ref3.ids;
    return ids && ids.some(function (id) {
      return typeof id === 'string';
    });
  },
  migrate: function migrate(attributes) {
    return _objectSpread(_objectSpread({}, attributes), {}, {
      ids: map(attributes.ids, function (id) {
        var parsedId = parseInt(id, 10);
        return Number.isInteger(parsedId) ? parsedId : null;
      })
    });
  },
  save: function save(_ref4) {
    var attributes = _ref4.attributes;
    var images = attributes.images,
        _attributes$columns2 = attributes.columns,
        columns = _attributes$columns2 === void 0 ? defaultColumnsNumber(attributes) : _attributes$columns2,
        imageCrop = attributes.imageCrop,
        caption = attributes.caption,
        linkTo = attributes.linkTo;
    return createElement("figure", {
      className: "columns-".concat(columns, " ").concat(imageCrop ? 'is-cropped' : '')
    }, createElement("ul", {
      className: "blocks-gallery-grid"
    }, images.map(function (image) {
      var href;

      switch (linkTo) {
        case 'media':
          href = image.fullUrl || image.url;
          break;

        case 'attachment':
          href = image.link;
          break;
      }

      var img = createElement("img", {
        src: image.url,
        alt: image.alt,
        "data-id": image.id,
        "data-full-url": image.fullUrl,
        "data-link": image.link,
        className: image.id ? "wp-image-".concat(image.id) : null
      });
      return createElement("li", {
        key: image.id || image.url,
        className: "blocks-gallery-item"
      }, createElement("figure", null, href ? createElement("a", {
        href: href
      }, img) : img, !RichText.isEmpty(image.caption) && createElement(RichText.Content, {
        tagName: "figcaption",
        className: "blocks-gallery-item__caption",
        value: image.caption
      })));
    })), !RichText.isEmpty(caption) && createElement(RichText.Content, {
      tagName: "figcaption",
      className: "blocks-gallery-caption",
      value: caption
    }));
  }
}, {
  attributes: {
    images: {
      type: 'array',
      default: [],
      source: 'query',
      selector: 'ul.wp-block-gallery .blocks-gallery-item',
      query: {
        url: {
          source: 'attribute',
          selector: 'img',
          attribute: 'src'
        },
        fullUrl: {
          source: 'attribute',
          selector: 'img',
          attribute: 'data-full-url'
        },
        alt: {
          source: 'attribute',
          selector: 'img',
          attribute: 'alt',
          default: ''
        },
        id: {
          source: 'attribute',
          selector: 'img',
          attribute: 'data-id'
        },
        link: {
          source: 'attribute',
          selector: 'img',
          attribute: 'data-link'
        },
        caption: {
          type: 'array',
          source: 'children',
          selector: 'figcaption'
        }
      }
    },
    ids: {
      type: 'array',
      default: []
    },
    columns: {
      type: 'number'
    },
    imageCrop: {
      type: 'boolean',
      default: true
    },
    linkTo: {
      type: 'string',
      default: 'none'
    }
  },
  supports: {
    align: true
  },
  save: function save(_ref5) {
    var attributes = _ref5.attributes;
    var images = attributes.images,
        _attributes$columns3 = attributes.columns,
        columns = _attributes$columns3 === void 0 ? defaultColumnsNumber(attributes) : _attributes$columns3,
        imageCrop = attributes.imageCrop,
        linkTo = attributes.linkTo;
    return createElement("ul", {
      className: "columns-".concat(columns, " ").concat(imageCrop ? 'is-cropped' : '')
    }, images.map(function (image) {
      var href;

      switch (linkTo) {
        case 'media':
          href = image.fullUrl || image.url;
          break;

        case 'attachment':
          href = image.link;
          break;
      }

      var img = createElement("img", {
        src: image.url,
        alt: image.alt,
        "data-id": image.id,
        "data-full-url": image.fullUrl,
        "data-link": image.link,
        className: image.id ? "wp-image-".concat(image.id) : null
      });
      return createElement("li", {
        key: image.id || image.url,
        className: "blocks-gallery-item"
      }, createElement("figure", null, href ? createElement("a", {
        href: href
      }, img) : img, image.caption && image.caption.length > 0 && createElement(RichText.Content, {
        tagName: "figcaption",
        value: image.caption
      })));
    }));
  }
}, {
  attributes: {
    images: {
      type: 'array',
      default: [],
      source: 'query',
      selector: 'ul.wp-block-gallery .blocks-gallery-item',
      query: {
        url: {
          source: 'attribute',
          selector: 'img',
          attribute: 'src'
        },
        alt: {
          source: 'attribute',
          selector: 'img',
          attribute: 'alt',
          default: ''
        },
        id: {
          source: 'attribute',
          selector: 'img',
          attribute: 'data-id'
        },
        link: {
          source: 'attribute',
          selector: 'img',
          attribute: 'data-link'
        },
        caption: {
          type: 'array',
          source: 'children',
          selector: 'figcaption'
        }
      }
    },
    columns: {
      type: 'number'
    },
    imageCrop: {
      type: 'boolean',
      default: true
    },
    linkTo: {
      type: 'string',
      default: 'none'
    }
  },
  isEligible: function isEligible(_ref6) {
    var images = _ref6.images,
        ids = _ref6.ids;
    return images && images.length > 0 && (!ids && images || ids && images && ids.length !== images.length || some(images, function (id, index) {
      if (!id && ids[index] !== null) {
        return true;
      }

      return parseInt(id, 10) !== ids[index];
    }));
  },
  migrate: function migrate(attributes) {
    return _objectSpread(_objectSpread({}, attributes), {}, {
      ids: map(attributes.images, function (_ref7) {
        var id = _ref7.id;

        if (!id) {
          return null;
        }

        return parseInt(id, 10);
      })
    });
  },
  supports: {
    align: true
  },
  save: function save(_ref8) {
    var attributes = _ref8.attributes;
    var images = attributes.images,
        _attributes$columns4 = attributes.columns,
        columns = _attributes$columns4 === void 0 ? defaultColumnsNumber(attributes) : _attributes$columns4,
        imageCrop = attributes.imageCrop,
        linkTo = attributes.linkTo;
    return createElement("ul", {
      className: "columns-".concat(columns, " ").concat(imageCrop ? 'is-cropped' : '')
    }, images.map(function (image) {
      var href;

      switch (linkTo) {
        case 'media':
          href = image.url;
          break;

        case 'attachment':
          href = image.link;
          break;
      }

      var img = createElement("img", {
        src: image.url,
        alt: image.alt,
        "data-id": image.id,
        "data-link": image.link,
        className: image.id ? "wp-image-".concat(image.id) : null
      });
      return createElement("li", {
        key: image.id || image.url,
        className: "blocks-gallery-item"
      }, createElement("figure", null, href ? createElement("a", {
        href: href
      }, img) : img, image.caption && image.caption.length > 0 && createElement(RichText.Content, {
        tagName: "figcaption",
        value: image.caption
      })));
    }));
  }
}, {
  attributes: {
    images: {
      type: 'array',
      default: [],
      source: 'query',
      selector: 'div.wp-block-gallery figure.blocks-gallery-image img',
      query: {
        url: {
          source: 'attribute',
          attribute: 'src'
        },
        alt: {
          source: 'attribute',
          attribute: 'alt',
          default: ''
        },
        id: {
          source: 'attribute',
          attribute: 'data-id'
        }
      }
    },
    columns: {
      type: 'number'
    },
    imageCrop: {
      type: 'boolean',
      default: true
    },
    linkTo: {
      type: 'string',
      default: 'none'
    },
    align: {
      type: 'string',
      default: 'none'
    }
  },
  supports: {
    align: true
  },
  save: function save(_ref9) {
    var attributes = _ref9.attributes;
    var images = attributes.images,
        _attributes$columns5 = attributes.columns,
        columns = _attributes$columns5 === void 0 ? defaultColumnsNumber(attributes) : _attributes$columns5,
        align = attributes.align,
        imageCrop = attributes.imageCrop,
        linkTo = attributes.linkTo;
    var className = classnames("columns-".concat(columns), {
      alignnone: align === 'none',
      'is-cropped': imageCrop
    });
    return createElement("div", {
      className: className
    }, images.map(function (image) {
      var href;

      switch (linkTo) {
        case 'media':
          href = image.url;
          break;

        case 'attachment':
          href = image.link;
          break;
      }

      var img = createElement("img", {
        src: image.url,
        alt: image.alt,
        "data-id": image.id
      });
      return createElement("figure", {
        key: image.id || image.url,
        className: "blocks-gallery-image"
      }, href ? createElement("a", {
        href: href
      }, img) : img);
    }));
  }
}];
export default deprecated;
//# sourceMappingURL=deprecated.js.map