import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { get, includes, invoke, isUndefined, pickBy } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { Component, RawHTML } from '@wordpress/element';
import { BaseControl, PanelBody, Placeholder, QueryControls, RadioControl, RangeControl, Spinner, ToggleControl, ToolbarGroup } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __, sprintf } from '@wordpress/i18n';
import { dateI18n, format, __experimentalGetSettings } from '@wordpress/date';
import { InspectorControls, BlockAlignmentToolbar, BlockControls, __experimentalImageSizeControl as ImageSizeControl } from '@wordpress/block-editor';
import { withSelect } from '@wordpress/data';
import { pin, list, grid } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import { MIN_EXCERPT_LENGTH, MAX_EXCERPT_LENGTH, MAX_POSTS_COLUMNS } from './constants';
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
  _inherits(LatestPostsEdit, _Component);

  var _super = _createSuper(LatestPostsEdit);

  function LatestPostsEdit() {
    var _this;

    _classCallCheck(this, LatestPostsEdit);

    _this = _super.apply(this, arguments);
    _this.state = {
      categoriesList: [],
      authorList: []
    };
    return _this;
  }

  _createClass(LatestPostsEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      this.isStillMounted = true;
      this.fetchRequest = apiFetch({
        path: addQueryArgs("/wp/v2/categories", CATEGORIES_LIST_QUERY)
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
      this.fetchRequest = apiFetch({
        path: addQueryArgs("/wp/v2/users", USERS_LIST_QUERY)
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
        return _objectSpread(_objectSpread({}, accumulator), {}, _defineProperty({}, category.name, category));
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

        if (includes(allCategories, null)) {
          return false;
        }

        setAttributes({
          categories: allCategories
        });
      };

      var inspectorControls = createElement(InspectorControls, null, createElement(PanelBody, {
        title: __('Post content settings')
      }, createElement(ToggleControl, {
        label: __('Post content'),
        checked: displayPostContent,
        onChange: function onChange(value) {
          return setAttributes({
            displayPostContent: value
          });
        }
      }), displayPostContent && createElement(RadioControl, {
        label: __('Show:'),
        selected: displayPostContentRadio,
        options: [{
          label: __('Excerpt'),
          value: 'excerpt'
        }, {
          label: __('Full post'),
          value: 'full_post'
        }],
        onChange: function onChange(value) {
          return setAttributes({
            displayPostContentRadio: value
          });
        }
      }), displayPostContent && displayPostContentRadio === 'excerpt' && createElement(RangeControl, {
        label: __('Max number of words in excerpt'),
        value: excerptLength,
        onChange: function onChange(value) {
          return setAttributes({
            excerptLength: value
          });
        },
        min: MIN_EXCERPT_LENGTH,
        max: MAX_EXCERPT_LENGTH
      })), createElement(PanelBody, {
        title: __('Post meta settings')
      }, createElement(ToggleControl, {
        label: __('Display author name'),
        checked: displayAuthor,
        onChange: function onChange(value) {
          return setAttributes({
            displayAuthor: value
          });
        }
      }), createElement(ToggleControl, {
        label: __('Display post date'),
        checked: displayPostDate,
        onChange: function onChange(value) {
          return setAttributes({
            displayPostDate: value
          });
        }
      })), createElement(PanelBody, {
        title: __('Featured image settings')
      }, createElement(ToggleControl, {
        label: __('Display featured image'),
        checked: displayFeaturedImage,
        onChange: function onChange(value) {
          return setAttributes({
            displayFeaturedImage: value
          });
        }
      }), displayFeaturedImage && createElement(Fragment, null, createElement(ImageSizeControl, {
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
      }), createElement(BaseControl, {
        className: "block-editor-image-alignment-control__row"
      }, createElement(BaseControl.VisualLabel, null, __('Image alignment')), createElement(BlockAlignmentToolbar, {
        value: featuredImageAlign,
        onChange: function onChange(value) {
          return setAttributes({
            featuredImageAlign: value
          });
        },
        controls: ['left', 'center', 'right'],
        isCollapsed: false
      })), createElement(ToggleControl, {
        label: __('Add link to featured image'),
        checked: addLinkToFeaturedImage,
        onChange: function onChange(value) {
          return setAttributes({
            addLinkToFeaturedImage: value
          });
        }
      }))), createElement(PanelBody, {
        title: __('Sorting and filtering')
      }, createElement(QueryControls, _extends({
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
      })), postLayout === 'grid' && createElement(RangeControl, {
        label: __('Columns'),
        value: columns,
        onChange: function onChange(value) {
          return setAttributes({
            columns: value
          });
        },
        min: 2,
        max: !hasPosts ? MAX_POSTS_COLUMNS : Math.min(MAX_POSTS_COLUMNS, latestPosts.length),
        required: true
      })));
      var hasPosts = Array.isArray(latestPosts) && latestPosts.length;

      if (!hasPosts) {
        return createElement(Fragment, null, inspectorControls, createElement(Placeholder, {
          icon: pin,
          label: __('Latest Posts')
        }, !Array.isArray(latestPosts) ? createElement(Spinner, null) : __('No posts found.')));
      } // Removing posts from display should be instant.


      var displayPosts = latestPosts.length > postsToShow ? latestPosts.slice(0, postsToShow) : latestPosts;
      var layoutControls = [{
        icon: list,
        title: __('List view'),
        onClick: function onClick() {
          return setAttributes({
            postLayout: 'list'
          });
        },
        isActive: postLayout === 'list'
      }, {
        icon: grid,
        title: __('Grid view'),
        onClick: function onClick() {
          return setAttributes({
            postLayout: 'grid'
          });
        },
        isActive: postLayout === 'grid'
      }];

      var dateFormat = __experimentalGetSettings().formats.date;

      return createElement(Fragment, null, inspectorControls, createElement(BlockControls, null, createElement(ToolbarGroup, {
        controls: layoutControls
      })), createElement("ul", {
        className: classnames(this.props.className, _defineProperty({
          'wp-block-latest-posts__list': true,
          'is-grid': postLayout === 'grid',
          'has-dates': displayPostDate,
          'has-author': displayAuthor
        }, "columns-".concat(columns), postLayout === 'grid'))
      }, displayPosts.map(function (post, i) {
        var titleTrimmed = invoke(post, ['title', 'rendered', 'trim']);
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
        var imageClasses = classnames(_defineProperty({
          'wp-block-latest-posts__featured-image': true
        }, "align".concat(featuredImageAlign), !!featuredImageAlign));
        var renderFeaturedImage = displayFeaturedImage && imageSourceUrl;
        var featuredImage = renderFeaturedImage && createElement("img", {
          src: imageSourceUrl,
          alt: featuredImageAlt,
          style: {
            maxWidth: featuredImageSizeWidth,
            maxHeight: featuredImageSizeHeight
          }
        });
        var needsReadMore = excerptLength < excerpt.trim().split(' ').length && post.excerpt.raw === '';
        var postExcerpt = needsReadMore ? createElement(Fragment, null, excerpt.trim().split(' ', excerptLength).join(' '), __(' … '), createElement("a", {
          href: post.link,
          target: "_blank",
          rel: "noopener noreferrer"
        }, __('Read more'))) : excerpt;
        return createElement("li", {
          key: i
        }, renderFeaturedImage && createElement("div", {
          className: imageClasses
        }, addLinkToFeaturedImage ? createElement("a", {
          href: post.link,
          target: "_blank",
          rel: "noreferrer noopener"
        }, featuredImage) : featuredImage), createElement("a", {
          href: post.link,
          target: "_blank",
          rel: "noreferrer noopener"
        }, titleTrimmed ? createElement(RawHTML, null, titleTrimmed) : __('(no title)')), displayAuthor && currentAuthor && createElement("div", {
          className: "wp-block-latest-posts__post-author"
        }, sprintf(
        /* translators: byline. %s: current author. */
        __('by %s'), currentAuthor.name)), displayPostDate && post.date_gmt && createElement("time", {
          dateTime: format('c', post.date_gmt),
          className: "wp-block-latest-posts__post-date"
        }, dateI18n(dateFormat, post.date_gmt)), displayPostContent && displayPostContentRadio === 'excerpt' && createElement("div", {
          className: "wp-block-latest-posts__post-excerpt"
        }, postExcerpt), displayPostContent && displayPostContentRadio === 'full_post' && createElement("div", {
          className: "wp-block-latest-posts__post-full-content"
        }, createElement(RawHTML, {
          key: "html"
        }, post.content.raw.trim())));
      })));
    }
  }]);

  return LatestPostsEdit;
}(Component);

export default withSelect(function (select, props) {
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
  var latestPostsQuery = pickBy({
    categories: catIds,
    author: selectedAuthor,
    order: order,
    orderby: orderBy,
    per_page: postsToShow
  }, function (value) {
    return !isUndefined(value);
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
    defaultImageWidth: get(imageDimensions, [featuredImageSizeSlug, 'width'], 0),
    defaultImageHeight: get(imageDimensions, [featuredImageSizeSlug, 'height'], 0),
    imageSizeOptions: imageSizeOptions,
    latestPosts: !Array.isArray(posts) ? posts : posts.map(function (post) {
      if (!post.featured_media) return post;
      var image = getMedia(post.featured_media);
      var url = get(image, ['media_details', 'sizes', featuredImageSizeSlug, 'source_url'], null);

      if (!url) {
        url = get(image, 'source_url', null);
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
//# sourceMappingURL=edit.js.map