import _extends from "@babel/runtime/helpers/esm/extends";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { TouchableWithoutFeedback, View, Text } from 'react-native';
import { isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { Component } from '@wordpress/element';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import { coreBlocks } from '@wordpress/block-library';
import { __ } from '@wordpress/i18n';
import { postList as icon } from '@wordpress/icons';
import { InspectorControls } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
import { Icon, PanelBody, ToggleControl, RangeControl, QueryControls } from '@wordpress/components';
/**
 * Internal dependencies
 */

import styles from './style.scss';
import { MIN_EXCERPT_LENGTH, MAX_EXCERPT_LENGTH } from './constants';

var LatestPostsEdit = /*#__PURE__*/function (_Component) {
  _inherits(LatestPostsEdit, _Component);

  var _super = _createSuper(LatestPostsEdit);

  function LatestPostsEdit() {
    var _this;

    _classCallCheck(this, LatestPostsEdit);

    _this = _super.apply(this, arguments);
    _this.state = {
      categoriesList: []
    };
    _this.onSetDisplayPostContent = _this.onSetDisplayPostContent.bind(_assertThisInitialized(_this));
    _this.onSetDisplayPostContentRadio = _this.onSetDisplayPostContentRadio.bind(_assertThisInitialized(_this));
    _this.onSetExcerptLength = _this.onSetExcerptLength.bind(_assertThisInitialized(_this));
    _this.onSetDisplayPostDate = _this.onSetDisplayPostDate.bind(_assertThisInitialized(_this));
    _this.onSetOrder = _this.onSetOrder.bind(_assertThisInitialized(_this));
    _this.onSetOrderBy = _this.onSetOrderBy.bind(_assertThisInitialized(_this));
    _this.onSetPostsToShow = _this.onSetPostsToShow.bind(_assertThisInitialized(_this));
    _this.onSetCategories = _this.onSetCategories.bind(_assertThisInitialized(_this));
    _this.getInspectorControls = _this.getInspectorControls.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(LatestPostsEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      this.isStillMounted = true;
      this.fetchRequest = apiFetch({
        path: '/wp/v2/categories'
      }).then(function (categoriesList) {
        if (_this2.isStillMounted) {
          _this2.setState({
            categoriesList: isEmpty(categoriesList) ? [] : categoriesList
          });
        }
      }).catch(function () {
        if (_this2.isStillMounted) {
          _this2.setState({
            categoriesList: []
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
    key: "onSetDisplayPostContent",
    value: function onSetDisplayPostContent(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        displayPostContent: value
      });
    }
  }, {
    key: "onSetDisplayPostContentRadio",
    value: function onSetDisplayPostContentRadio(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        displayPostContentRadio: value ? 'excerpt' : 'full_post'
      });
    }
  }, {
    key: "onSetExcerptLength",
    value: function onSetExcerptLength(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        excerptLength: value
      });
    }
  }, {
    key: "onSetDisplayPostDate",
    value: function onSetDisplayPostDate(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        displayPostDate: value
      });
    }
  }, {
    key: "onSetOrder",
    value: function onSetOrder(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        order: value
      });
    }
  }, {
    key: "onSetOrderBy",
    value: function onSetOrderBy(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        orderBy: value
      });
    }
  }, {
    key: "onSetPostsToShow",
    value: function onSetPostsToShow(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        postsToShow: value
      });
    }
  }, {
    key: "onSetCategories",
    value: function onSetCategories(value) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        categories: '' !== value ? value.toString() : undefined
      });
    }
  }, {
    key: "getInspectorControls",
    value: function getInspectorControls() {
      var attributes = this.props.attributes;
      var displayPostContent = attributes.displayPostContent,
          displayPostContentRadio = attributes.displayPostContentRadio,
          excerptLength = attributes.excerptLength,
          displayPostDate = attributes.displayPostDate,
          order = attributes.order,
          orderBy = attributes.orderBy,
          postsToShow = attributes.postsToShow,
          categories = attributes.categories;
      var categoriesList = this.state.categoriesList;
      var displayExcerptPostContent = displayPostContentRadio === 'excerpt';
      return createElement(InspectorControls, null, createElement(PanelBody, {
        title: __('Post content settings')
      }, createElement(ToggleControl, {
        label: __('Show post content'),
        checked: displayPostContent,
        onChange: this.onSetDisplayPostContent
      }), displayPostContent && createElement(ToggleControl, {
        label: __('Only show excerpt'),
        checked: displayExcerptPostContent,
        onChange: this.onSetDisplayPostContentRadio
      }), displayPostContent && displayExcerptPostContent && createElement(RangeControl, {
        label: __('Excerpt length (words)'),
        value: excerptLength,
        onChange: this.onSetExcerptLength,
        min: MIN_EXCERPT_LENGTH,
        max: MAX_EXCERPT_LENGTH
      })), createElement(PanelBody, {
        title: __('Post meta settings')
      }, createElement(ToggleControl, {
        label: __('Display post date'),
        checked: displayPostDate,
        onChange: this.onSetDisplayPostDate
      })), createElement(PanelBody, {
        title: __('Sorting and filtering')
      }, createElement(QueryControls, _extends({
        order: order,
        orderBy: orderBy
      }, {
        numberOfItems: postsToShow,
        categoriesList: categoriesList,
        selectedCategoryId: undefined !== categories ? Number(categories) : '',
        onOrderChange: this.onSetOrder,
        onOrderByChange: this.onSetOrderBy,
        onCategoryChange: // eslint-disable-next-line no-undef
        __DEV__ ? this.onSetCategories : undefined,
        onNumberOfItemsChange: this.onSetPostsToShow
      }))));
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          getStylesFromColorScheme = _this$props.getStylesFromColorScheme,
          name = _this$props.name,
          openGeneralSidebar = _this$props.openGeneralSidebar,
          isSelected = _this$props.isSelected;
      var blockType = coreBlocks[name];
      var blockStyle = getStylesFromColorScheme(styles.latestPostBlock, styles.latestPostBlockDark);
      var iconStyle = getStylesFromColorScheme(styles.latestPostBlockIcon, styles.latestPostBlockIconDark);
      var titleStyle = getStylesFromColorScheme(styles.latestPostBlockMessage, styles.latestPostBlockMessageDark);
      return createElement(TouchableWithoutFeedback, {
        accessible: !isSelected,
        disabled: !isSelected,
        onPress: openGeneralSidebar
      }, createElement(View, {
        style: blockStyle
      }, this.getInspectorControls(), createElement(Icon, _extends({
        icon: icon
      }, iconStyle)), createElement(Text, {
        style: titleStyle
      }, blockType.settings.title), createElement(Text, {
        style: styles.latestPostBlockSubtitle
      }, __('CUSTOMIZE'))));
    }
  }]);

  return LatestPostsEdit;
}(Component);

export default compose([withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/edit-post'),
      _openGeneralSidebar = _dispatch.openGeneralSidebar;

  return {
    openGeneralSidebar: function openGeneralSidebar() {
      return _openGeneralSidebar('edit-post/block');
    }
  };
}), withPreferredColorScheme])(LatestPostsEdit);
//# sourceMappingURL=edit.native.js.map