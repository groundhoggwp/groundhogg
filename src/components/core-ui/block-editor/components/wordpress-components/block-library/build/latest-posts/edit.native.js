"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _blockLibrary = require("@wordpress/block-library");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _blockEditor = require("@wordpress/block-editor");

var _apiFetch = _interopRequireDefault(require("@wordpress/api-fetch"));

var _components = require("@wordpress/components");

var _style = _interopRequireDefault(require("./style.scss"));

var _constants = require("./constants");

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var LatestPostsEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(LatestPostsEdit, _Component);

  var _super = _createSuper(LatestPostsEdit);

  function LatestPostsEdit() {
    var _this;

    (0, _classCallCheck2.default)(this, LatestPostsEdit);
    _this = _super.apply(this, arguments);
    _this.state = {
      categoriesList: []
    };
    _this.onSetDisplayPostContent = _this.onSetDisplayPostContent.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetDisplayPostContentRadio = _this.onSetDisplayPostContentRadio.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetExcerptLength = _this.onSetExcerptLength.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetDisplayPostDate = _this.onSetDisplayPostDate.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetOrder = _this.onSetOrder.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetOrderBy = _this.onSetOrderBy.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetPostsToShow = _this.onSetPostsToShow.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetCategories = _this.onSetCategories.bind((0, _assertThisInitialized2.default)(_this));
    _this.getInspectorControls = _this.getInspectorControls.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(LatestPostsEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      this.isStillMounted = true;
      this.fetchRequest = (0, _apiFetch.default)({
        path: '/wp/v2/categories'
      }).then(function (categoriesList) {
        if (_this2.isStillMounted) {
          _this2.setState({
            categoriesList: (0, _lodash.isEmpty)(categoriesList) ? [] : categoriesList
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
      return (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Post content settings')
      }, (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Show post content'),
        checked: displayPostContent,
        onChange: this.onSetDisplayPostContent
      }), displayPostContent && (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Only show excerpt'),
        checked: displayExcerptPostContent,
        onChange: this.onSetDisplayPostContentRadio
      }), displayPostContent && displayExcerptPostContent && (0, _element.createElement)(_components.RangeControl, {
        label: (0, _i18n.__)('Excerpt length (words)'),
        value: excerptLength,
        onChange: this.onSetExcerptLength,
        min: _constants.MIN_EXCERPT_LENGTH,
        max: _constants.MAX_EXCERPT_LENGTH
      })), (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Post meta settings')
      }, (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Display post date'),
        checked: displayPostDate,
        onChange: this.onSetDisplayPostDate
      })), (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Sorting and filtering')
      }, (0, _element.createElement)(_components.QueryControls, (0, _extends2.default)({
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
      var blockType = _blockLibrary.coreBlocks[name];
      var blockStyle = getStylesFromColorScheme(_style.default.latestPostBlock, _style.default.latestPostBlockDark);
      var iconStyle = getStylesFromColorScheme(_style.default.latestPostBlockIcon, _style.default.latestPostBlockIconDark);
      var titleStyle = getStylesFromColorScheme(_style.default.latestPostBlockMessage, _style.default.latestPostBlockMessageDark);
      return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
        accessible: !isSelected,
        disabled: !isSelected,
        onPress: openGeneralSidebar
      }, (0, _element.createElement)(_reactNative.View, {
        style: blockStyle
      }, this.getInspectorControls(), (0, _element.createElement)(_components.Icon, (0, _extends2.default)({
        icon: _icons.postList
      }, iconStyle)), (0, _element.createElement)(_reactNative.Text, {
        style: titleStyle
      }, blockType.settings.title), (0, _element.createElement)(_reactNative.Text, {
        style: _style.default.latestPostBlockSubtitle
      }, (0, _i18n.__)('CUSTOMIZE'))));
    }
  }]);
  return LatestPostsEdit;
}(_element.Component);

var _default = (0, _compose.compose)([(0, _data.withDispatch)(function (dispatch) {
  var _dispatch = dispatch('core/edit-post'),
      _openGeneralSidebar = _dispatch.openGeneralSidebar;

  return {
    openGeneralSidebar: function openGeneralSidebar() {
      return _openGeneralSidebar('edit-post/block');
    }
  };
}), _compose.withPreferredColorScheme])(LatestPostsEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map