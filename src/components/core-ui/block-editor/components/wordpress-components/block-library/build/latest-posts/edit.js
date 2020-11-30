"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _lodash = require("lodash");

var _classnames3 = _interopRequireDefault(require("classnames"));

var _components = require("@wordpress/components");

var _apiFetch = _interopRequireDefault(require("@wordpress/api-fetch"));

var _url = require("@wordpress/url");

var _i18n = require("@wordpress/i18n");

var _date = require("@wordpress/date");

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

var _constants = require("./constants");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * Module Constants
 */
var CATEGORIES_LIST_QUERY = {
  per_page: -1
};
var USERS_LIST_QUERY = {
  per_page: -1
};

var LatestPostsEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(LatestPostsEdit, _Component);

  var _super = _createSuper(LatestPostsEdit);

  function LatestPostsEdit() {
    var _this;

    (0, _classCallCheck2.default)(this, LatestPostsEdit);
    _this = _super.apply(this, arguments);
    _this.state = {
      categoriesList: [],
      authorList: []
    };
    return _this;
  }

  (0, _createClass2.default)(LatestPostsEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      this.isStillMounted = true;
      this.fetchRequest = (0, _apiFetch.default)({
        path: (0, _url.addQueryArgs)("/wp/v2/categories", CATEGORIES_LIST_QUERY)
      }).then(function (categoriesList) {
        if (_this2.isStillMounted) {
          _this2.setState({
            categoriesList: categoriesList
          });
        }
      }).catch(function () {
        if (_this2.isStillMounted) {
          _this2.setState({
            categoriesList: []
          });
        }
      });
      this.fetchRequest = (0, _apiFetch.default)({
        path: (0, _url.addQueryArgs)("/wp/v2/users", USERS_LIST_QUERY)
      }).then(function (authorList) {
        if (_this2.isStillMounted) {
          _this2.setState({
            authorList: authorList
          });
        }
      }).catch(function () {
        if (_this2.isStillMounted) {
          _this2.setState({
            authorList: []
          });
        }
      });
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.isStillMounted = false;
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          attributes = _this$props.attributes,
          setAttributes = _this$props.setAttributes,
          imageSizeOptions = _this$props.imageSizeOptions,
          latestPosts = _this$props.latestPosts,
          defaultImageWidth = _this$props.defaultImageWidth,
          defaultImageHeight = _this$props.defaultImageHeight;
      var _this$state = this.state,
          categoriesList = _this$state.categoriesList,
          authorList = _this$state.authorList;
      var displayFeaturedImage = attributes.displayFeaturedImage,
          displayPostContentRadio = attributes.displayPostContentRadio,
          displayPostContent = attributes.displayPostContent,
          displayPostDate = attributes.displayPostDate,
          displayAuthor = attributes.displayAuthor,
          postLayout = attributes.postLayout,
          columns = attributes.columns,
          order = attributes.order,
          orderBy = attributes.orderBy,
          categories = attributes.categories,
          selectedAuthor = attributes.selectedAuthor,
          postsToShow = attributes.postsToShow,
          excerptLength = attributes.excerptLength,
          featuredImageAlign = attributes.featuredImageAlign,
          featuredImageSizeSlug = attributes.featuredImageSizeSlug,
          featuredImageSizeWidth = attributes.featuredImageSizeWidth,
          featuredImageSizeHeight = attributes.featuredImageSizeHeight,
          addLinkToFeaturedImage = attributes.addLinkToFeaturedImage;
      var categorySuggestions = categoriesList.reduce(function (accumulator, category) {
        return _objectSpread(_objectSpread({}, accumulator), {}, (0, _defineProperty2.default)({}, category.name, category));
      }, {});

      var selectCategories = function selectCategories(tokens) {
        var hasNoSuggestion = tokens.some(function (token) {
          return typeof token === 'string' && !categorySuggestions[token];
        });

        if (hasNoSuggestion) {
          return;
        } // Categories that are already will be objects, while new additions will be strings (the name).
        // allCategories nomalizes the array so that they are all objects.


        var allCategories = tokens.map(function (token) {
          return typeof token === 'string' ? categorySuggestions[token] : token;
        }); // We do nothing if the category is not selected
        // from suggestions.

        if ((0, _lodash.includes)(allCategories, null)) {
          return false;
        }

        setAttributes({
          categories: allCategories
        });
      };

      var inspectorControls = (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Post content settings')
      }, (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Post content'),
        checked: displayPostContent,
        onChange: function onChange(value) {
          return setAttributes({
            displayPostContent: value
          });
        }
      }), displayPostContent && (0, _element.createElement)(_components.RadioControl, {
        label: (0, _i18n.__)('Show:'),
        selected: displayPostContentRadio,
        options: [{
          label: (0, _i18n.__)('Excerpt'),
          value: 'excerpt'
        }, {
          label: (0, _i18n.__)('Full post'),
          value: 'full_post'
        }],
        onChange: function onChange(value) {
          return setAttributes({
            displayPostContentRadio: value
          });
        }
      }), displayPostContent && displayPostContentRadio === 'excerpt' && (0, _element.createElement)(_components.RangeControl, {
        label: (0, _i18n.__)('Max number of words in excerpt'),
        value: excerptLength,
        onChange: function onChange(value) {
          return setAttributes({
            excerptLength: value
          });
        },
        min: _constants.MIN_EXCERPT_LENGTH,
        max: _constants.MAX_EXCERPT_LENGTH
      })), (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Post meta settings')
      }, (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Display author name'),
        checked: displayAuthor,
        onChange: function onChange(value) {
          return setAttributes({
            displayAuthor: value
          });
        }
      }), (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Display post date'),
        checked: displayPostDate,
        onChange: function onChange(value) {
          return setAttributes({
            displayPostDate: value
          });
        }
      })), (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Featured image settings')
      }, (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Display featured image'),
        checked: displayFeaturedImage,
        onChange: function onChange(value) {
          return setAttributes({
            displayFeaturedImage: value
          });
        }
      }), displayFeaturedImage && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.__experimentalImageSizeControl, {
        onChange: function onChange(value) {
          var newAttrs = {};

          if (value.hasOwnProperty('width')) {
            newAttrs.featuredImageSizeWidth = value.width;
          }

          if (value.hasOwnProperty('height')) {
            newAttrs.featuredImageSizeHeight = value.height;
          }

          setAttributes(newAttrs);
        },
        slug: featuredImageSizeSlug,
        width: featuredImageSizeWidth,
        height: featuredImageSizeHeight,
        imageWidth: defaultImageWidth,
        imageHeight: defaultImageHeight,
        imageSizeOptions: imageSizeOptions,
        onChangeImage: function onChangeImage(value) {
          return setAttributes({
            featuredImageSizeSlug: value,
            featuredImageSizeWidth: undefined,
            featuredImageSizeHeight: undefined
          });
        }
      }), (0, _element.createElement)(_components.BaseControl, {
        className: "block-editor-image-alignment-control__row"
      }, (0, _element.createElement)(_components.BaseControl.VisualLabel, null, (0, _i18n.__)('Image alignment')), (0, _element.createElement)(_blockEditor.BlockAlignmentToolbar, {
        value: featuredImageAlign,
        onChange: function onChange(value) {
          return setAttributes({
            featuredImageAlign: value
          });
        },
        controls: ['left', 'center', 'right'],
        isCollapsed: false
      })), (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Add link to featured image'),
        checked: addLinkToFeaturedImage,
        onChange: function onChange(value) {
          return setAttributes({
            addLinkToFeaturedImage: value
          });
        }
      }))), (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Sorting and filtering')
      }, (0, _element.createElement)(_components.QueryControls, (0, _extends2.default)({
        order: order,
        orderBy: orderBy
      }, {
        numberOfItems: postsToShow,
        onOrderChange: function onOrderChange(value) {
          return setAttributes({
            order: value
          });
        },
        onOrderByChange: function onOrderByChange(value) {
          return setAttributes({
            orderBy: value
          });
        },
        onNumberOfItemsChange: function onNumberOfItemsChange(value) {
          return setAttributes({
            postsToShow: value
          });
        },
        categorySuggestions: categorySuggestions,
        onCategoryChange: selectCategories,
        selectedCategories: categories,
        onAuthorChange: function onAuthorChange(value) {
          return setAttributes({
            selectedAuthor: '' !== value ? Number(value) : undefined
          });
        },
        authorList: authorList,
        selectedAuthorId: selectedAuthor
      })), postLayout === 'grid' && (0, _element.createElement)(_components.RangeControl, {
        label: (0, _i18n.__)('Columns'),
        value: columns,
        onChange: function onChange(value) {
          return setAttributes({
            columns: value
          });
        },
        min: 2,
        max: !hasPosts ? _constants.MAX_POSTS_COLUMNS : Math.min(_constants.MAX_POSTS_COLUMNS, latestPosts.length),
        required: true
      })));
      var hasPosts = Array.isArray(latestPosts) && latestPosts.length;

      if (!hasPosts) {
        return (0, _element.createElement)(_element.Fragment, null, inspectorControls, (0, _element.createElement)(_components.Placeholder, {
          icon: _icons.pin,
          label: (0, _i18n.__)('Latest Posts')
        }, !Array.isArray(latestPosts) ? (0, _element.createElement)(_components.Spinner, null) : (0, _i18n.__)('No posts found.')));
      } // Removing posts from display should be instant.


      var displayPosts = latestPosts.length > postsToShow ? latestPosts.slice(0, postsToShow) : latestPosts;
      var layoutControls = [{
        icon: _icons.list,
        title: (0, _i18n.__)('List view'),
        onClick: function onClick() {
          return setAttributes({
            postLayout: 'list'
          });
        },
        isActive: postLayout === 'list'
      }, {
        icon: _icons.grid,
        title: (0, _i18n.__)('Grid view'),
        onClick: function onClick() {
          return setAttributes({
            postLayout: 'grid'
          });
        },
        isActive: postLayout === 'grid'
      }];
      var dateFormat = (0, _date.__experimentalGetSettings)().formats.date;
      return (0, _element.createElement)(_element.Fragment, null, inspectorControls, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, {
        controls: layoutControls
      })), (0, _element.createElement)("ul", {
        className: (0, _classnames3.default)(this.props.className, (0, _defineProperty2.default)({
          'wp-block-latest-posts__list': true,
          'is-grid': postLayout === 'grid',
          'has-dates': displayPostDate,
          'has-author': displayAuthor
        }, "columns-".concat(columns), postLayout === 'grid'))
      }, displayPosts.map(function (post, i) {
        var titleTrimmed = (0, _lodash.invoke)(post, ['title', 'rendered', 'trim']);
        var excerpt = post.excerpt.rendered;
        var currentAuthor = authorList.find(function (author) {
          return author.id === post.author;
        });
        var excerptElement = document.createElement('div');
        excerptElement.innerHTML = excerpt;
        excerpt = excerptElement.textContent || excerptElement.innerText || '';
        var _post$featuredImageIn = post.featuredImageInfo;
        _post$featuredImageIn = _post$featuredImageIn === void 0 ? {} : _post$featuredImageIn;
        var imageSourceUrl = _post$featuredImageIn.url,
            featuredImageAlt = _post$featuredImageIn.alt;
        var imageClasses = (0, _classnames3.default)((0, _defineProperty2.default)({
          'wp-block-latest-posts__featured-image': true
        }, "align".concat(featuredImageAlign), !!featuredImageAlign));
        var renderFeaturedImage = displayFeaturedImage && imageSourceUrl;
        var featuredImage = renderFeaturedImage && (0, _element.createElement)("img", {
          src: imageSourceUrl,
          alt: featuredImageAlt,
          style: {
            maxWidth: featuredImageSizeWidth,
            maxHeight: featuredImageSizeHeight
          }
        });
        var needsReadMore = excerptLength < excerpt.trim().split(' ').length && post.excerpt.raw === '';
        var postExcerpt = needsReadMore ? (0, _element.createElement)(_element.Fragment, null, excerpt.trim().split(' ', excerptLength).join(' '), (0, _i18n.__)(' … '), (0, _element.createElement)("a", {
          href: post.link,
          target: "_blank",
          rel: "noopener noreferrer"
        }, (0, _i18n.__)('Read more'))) : excerpt;
        return (0, _element.createElement)("li", {
          key: i
        }, renderFeaturedImage && (0, _element.createElement)("div", {
          className: imageClasses
        }, addLinkToFeaturedImage ? (0, _element.createElement)("a", {
          href: post.link,
          target: "_blank",
          rel: "noreferrer noopener"
        }, featuredImage) : featuredImage), (0, _element.createElement)("a", {
          href: post.link,
          target: "_blank",
          rel: "noreferrer noopener"
        }, titleTrimmed ? (0, _element.createElement)(_element.RawHTML, null, titleTrimmed) : (0, _i18n.__)('(no title)')), displayAuthor && currentAuthor && (0, _element.createElement)("div", {
          className: "wp-block-latest-posts__post-author"
        }, (0, _i18n.sprintf)(
        /* translators: byline. %s: current author. */
        (0, _i18n.__)('by %s'), currentAuthor.name)), displayPostDate && post.date_gmt && (0, _element.createElement)("time", {
          dateTime: (0, _date.format)('c', post.date_gmt),
          className: "wp-block-latest-posts__post-date"
        }, (0, _date.dateI18n)(dateFormat, post.date_gmt)), displayPostContent && displayPostContentRadio === 'excerpt' && (0, _element.createElement)("div", {
          className: "wp-block-latest-posts__post-excerpt"
        }, postExcerpt), displayPostContent && displayPostContentRadio === 'full_post' && (0, _element.createElement)("div", {
          className: "wp-block-latest-posts__post-full-content"
        }, (0, _element.createElement)(_element.RawHTML, {
          key: "html"
        }, post.content.raw.trim())));
      })));
    }
  }]);
  return LatestPostsEdit;
}(_element.Component);

var _default = (0, _data.withSelect)(function (select, props) {
  var _props$attributes = props.attributes,
      featuredImageSizeSlug = _props$attributes.featuredImageSizeSlug,
      postsToShow = _props$attributes.postsToShow,
      order = _props$attributes.order,
      orderBy = _props$attributes.orderBy,
      categories = _props$attributes.categories,
      selectedAuthor = _props$attributes.selectedAuthor;

  var _select = select('core'),
      getEntityRecords = _select.getEntityRecords,
      getMedia = _select.getMedia;

  var _select2 = select('core/block-editor'),
      getSettings = _select2.getSettings;

  var _getSettings = getSettings(),
      imageSizes = _getSettings.imageSizes,
      imageDimensions = _getSettings.imageDimensions;

  var catIds = categories && categories.length > 0 ? categories.map(function (cat) {
    return cat.id;
  }) : [];
  var latestPostsQuery = (0, _lodash.pickBy)({
    categories: catIds,
    author: selectedAuthor,
    order: order,
    orderby: orderBy,
    per_page: postsToShow
  }, function (value) {
    return !(0, _lodash.isUndefined)(value);
  });
  var posts = getEntityRecords('postType', 'post', latestPostsQuery);
  var imageSizeOptions = imageSizes.filter(function (_ref) {
    var slug = _ref.slug;
    return slug !== 'full';
  }).map(function (_ref2) {
    var name = _ref2.name,
        slug = _ref2.slug;
    return {
      value: slug,
      label: name
    };
  });
  return {
    defaultImageWidth: (0, _lodash.get)(imageDimensions, [featuredImageSizeSlug, 'width'], 0),
    defaultImageHeight: (0, _lodash.get)(imageDimensions, [featuredImageSizeSlug, 'height'], 0),
    imageSizeOptions: imageSizeOptions,
    latestPosts: !Array.isArray(posts) ? posts : posts.map(function (post) {
      if (!post.featured_media) return post;
      var image = getMedia(post.featured_media);
      var url = (0, _lodash.get)(image, ['media_details', 'sizes', featuredImageSizeSlug, 'source_url'], null);

      if (!url) {
        url = (0, _lodash.get)(image, 'source_url', null);
      }

      var featuredImageInfo = {
        url: url,
        // eslint-disable-next-line camelcase
        alt: image === null || image === void 0 ? void 0 : image.alt_text
      };
      return _objectSpread(_objectSpread({}, post), {}, {
        featuredImageInfo: featuredImageInfo
      });
    })
  };
})(LatestPostsEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map