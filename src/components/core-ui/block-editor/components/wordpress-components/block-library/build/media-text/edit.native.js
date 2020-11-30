"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _lodash = require("lodash");

var _reactNative = require("react-native");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

var _mediaContainer = _interopRequireDefault(require("./media-container"));

var _style = _interopRequireDefault(require("./style.scss"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var TEMPLATE = [['core/paragraph']]; // this limits the resize to a safe zone to avoid making broken layouts

var WIDTH_CONSTRAINT_PERCENTAGE = 15;
var BREAKPOINTS = {
  mobile: 480
};

var applyWidthConstraints = function applyWidthConstraints(width) {
  return Math.max(WIDTH_CONSTRAINT_PERCENTAGE, Math.min(width, 100 - WIDTH_CONSTRAINT_PERCENTAGE));
};

var MediaTextEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(MediaTextEdit, _Component);

  var _super = _createSuper(MediaTextEdit);

  function MediaTextEdit() {
    var _this;

    (0, _classCallCheck2.default)(this, MediaTextEdit);
    _this = _super.apply(this, arguments);
    _this.onSelectMedia = _this.onSelectMedia.bind((0, _assertThisInitialized2.default)(_this));
    _this.onMediaUpdate = _this.onMediaUpdate.bind((0, _assertThisInitialized2.default)(_this));
    _this.onWidthChange = _this.onWidthChange.bind((0, _assertThisInitialized2.default)(_this));
    _this.commitWidthChange = _this.commitWidthChange.bind((0, _assertThisInitialized2.default)(_this));
    _this.onLayoutChange = _this.onLayoutChange.bind((0, _assertThisInitialized2.default)(_this));
    _this.onMediaSelected = _this.onMediaSelected.bind((0, _assertThisInitialized2.default)(_this));
    _this.onReplaceMedia = _this.onReplaceMedia.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetOpenPickerRef = _this.onSetOpenPickerRef.bind((0, _assertThisInitialized2.default)(_this));
    _this.onSetImageFill = _this.onSetImageFill.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      mediaWidth: null,
      containerWidth: 0,
      isMediaSelected: false
    };
    return _this;
  }

  (0, _createClass2.default)(MediaTextEdit, [{
    key: "onSelectMedia",
    value: function onSelectMedia(media) {
      var setAttributes = this.props.setAttributes;
      var mediaType;
      var src; // for media selections originated from a file upload.

      if (media.media_type) {
        if (media.media_type === 'image') {
          mediaType = 'image';
        } else {
          // only images and videos are accepted so if the media_type is not an image we can assume it is a video.
          // video contain the media type of 'file' in the object returned from the rest api.
          mediaType = 'video';
        }
      } else {
        // for media selections originated from existing files in the media library.
        mediaType = media.type;
      }

      if (mediaType === 'image' && media.sizes) {
        // Try the "large" size URL, falling back to the "full" size URL below.
        src = (0, _lodash.get)(media, ['sizes', 'large', 'url']) || (0, _lodash.get)(media, ['media_details', 'sizes', 'large', 'source_url']);
      }

      setAttributes({
        mediaAlt: media.alt,
        mediaId: media.id,
        mediaType: mediaType,
        mediaUrl: src || media.url,
        imageFill: undefined,
        focalPoint: undefined
      });
    }
  }, {
    key: "onMediaUpdate",
    value: function onMediaUpdate(media) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        mediaId: media.id,
        mediaUrl: media.url
      });
    }
  }, {
    key: "onWidthChange",
    value: function onWidthChange(width) {
      this.setState({
        mediaWidth: applyWidthConstraints(width)
      });
    }
  }, {
    key: "commitWidthChange",
    value: function commitWidthChange(width) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        mediaWidth: applyWidthConstraints(width)
      });
      this.setState({
        mediaWidth: null
      });
    }
  }, {
    key: "onLayoutChange",
    value: function onLayoutChange(_ref) {
      var nativeEvent = _ref.nativeEvent;
      var width = nativeEvent.layout.width;
      var containerWidth = this.state.containerWidth;

      if (containerWidth === width) {
        return null;
      }

      this.setState({
        containerWidth: width
      });
    }
  }, {
    key: "onMediaSelected",
    value: function onMediaSelected() {
      this.setState({
        isMediaSelected: true
      });
    }
  }, {
    key: "onReplaceMedia",
    value: function onReplaceMedia() {
      if (this.openPickerRef) {
        this.openPickerRef();
      }
    }
  }, {
    key: "onSetOpenPickerRef",
    value: function onSetOpenPickerRef(openPicker) {
      this.openPickerRef = openPicker;
    }
  }, {
    key: "onSetImageFill",
    value: function onSetImageFill() {
      var _this$props = this.props,
          attributes = _this$props.attributes,
          setAttributes = _this$props.setAttributes;
      var imageFill = attributes.imageFill;
      setAttributes({
        imageFill: !imageFill
      });
    }
  }, {
    key: "getControls",
    value: function getControls() {
      var attributes = this.props.attributes;
      var imageFill = attributes.imageFill;
      return (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Media & Text settings')
      }, (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Crop image to fill entire column'),
        checked: imageFill,
        onChange: this.onSetImageFill
      })));
    }
  }, {
    key: "renderMediaArea",
    value: function renderMediaArea(shouldStack) {
      var _this$state = this.state,
          isMediaSelected = _this$state.isMediaSelected,
          containerWidth = _this$state.containerWidth;
      var _this$props2 = this.props,
          attributes = _this$props2.attributes,
          isSelected = _this$props2.isSelected;
      var mediaAlt = attributes.mediaAlt,
          mediaId = attributes.mediaId,
          mediaPosition = attributes.mediaPosition,
          mediaType = attributes.mediaType,
          mediaUrl = attributes.mediaUrl,
          mediaWidth = attributes.mediaWidth,
          imageFill = attributes.imageFill,
          focalPoint = attributes.focalPoint,
          verticalAlignment = attributes.verticalAlignment;
      var mediaAreaWidth = mediaWidth && !shouldStack ? containerWidth * mediaWidth / 100 - _style.default.mediaAreaPadding.width : containerWidth;

      var aligmentStyles = _style.default["is-vertically-aligned-".concat(verticalAlignment || 'center')];

      return (0, _element.createElement)(_mediaContainer.default, (0, _extends2.default)({
        commitWidthChange: this.commitWidthChange,
        isMediaSelected: isMediaSelected,
        onFocus: this.props.onFocus,
        onMediaSelected: this.onMediaSelected,
        onMediaUpdate: this.onMediaUpdate,
        onSelectMedia: this.onSelectMedia,
        onSetOpenPickerRef: this.onSetOpenPickerRef,
        onWidthChange: this.onWidthChange,
        mediaWidth: mediaAreaWidth
      }, {
        mediaAlt: mediaAlt,
        mediaId: mediaId,
        mediaType: mediaType,
        mediaUrl: mediaUrl,
        mediaPosition: mediaPosition,
        imageFill: imageFill,
        focalPoint: focalPoint,
        isSelected: isSelected,
        aligmentStyles: aligmentStyles,
        shouldStack: shouldStack
      }));
    }
  }, {
    key: "render",
    value: function render() {
      var _wrapperProps$style;

      var _this$props3 = this.props,
          attributes = _this$props3.attributes,
          backgroundColor = _this$props3.backgroundColor,
          setAttributes = _this$props3.setAttributes,
          isSelected = _this$props3.isSelected,
          isRTL = _this$props3.isRTL,
          wrapperProps = _this$props3.wrapperProps;
      var isStackedOnMobile = attributes.isStackedOnMobile,
          imageFill = attributes.imageFill,
          mediaPosition = attributes.mediaPosition,
          mediaWidth = attributes.mediaWidth,
          mediaType = attributes.mediaType,
          verticalAlignment = attributes.verticalAlignment;
      var _this$state2 = this.state,
          containerWidth = _this$state2.containerWidth,
          isMediaSelected = _this$state2.isMediaSelected;
      var isMobile = containerWidth < BREAKPOINTS.mobile;
      var shouldStack = isStackedOnMobile && isMobile;
      var temporaryMediaWidth = shouldStack ? 100 : this.state.mediaWidth || mediaWidth;
      var widthString = "".concat(temporaryMediaWidth, "%");
      var innerBlockContainerStyle = !shouldStack ? _style.default.innerBlock : _objectSpread({}, mediaPosition === 'left' ? _style.default.innerBlockStackMediaLeft : _style.default.innerBlockStackMediaRight);

      var containerStyles = _objectSpread(_objectSpread(_objectSpread(_objectSpread(_objectSpread(_objectSpread(_objectSpread({}, _style.default['wp-block-media-text']), _style.default["is-vertically-aligned-".concat(verticalAlignment || 'center')]), mediaPosition === 'right' ? _style.default['has-media-on-the-right'] : {}), shouldStack && _style.default['is-stacked-on-mobile']), shouldStack && mediaPosition === 'right' ? _style.default['is-stacked-on-mobile.has-media-on-the-right'] : {}), isSelected && _style.default['is-selected']), {}, {
        backgroundColor: (wrapperProps === null || wrapperProps === void 0 ? void 0 : (_wrapperProps$style = wrapperProps.style) === null || _wrapperProps$style === void 0 ? void 0 : _wrapperProps$style.backgroundColor) || backgroundColor.color,
        paddingBottom: 0
      });

      var innerBlockWidth = shouldStack ? 100 : 100 - temporaryMediaWidth;
      var innerBlockWidthString = "".concat(innerBlockWidth, "%");
      var mediaContainerStyle = [{
        flex: 1
      }, shouldStack ? _objectSpread(_objectSpread({}, mediaPosition === 'left' && _style.default.mediaStackLeft), mediaPosition === 'right' && _style.default.mediaStackRight) : _objectSpread(_objectSpread({}, mediaPosition === 'left' && _style.default.mediaLeft), mediaPosition === 'right' && _style.default.mediaRight)];
      var toolbarControls = [{
        icon: isRTL ? _icons.pullRight : _icons.pullLeft,
        title: (0, _i18n.__)('Show media on left'),
        isActive: mediaPosition === 'left',
        onClick: function onClick() {
          return setAttributes({
            mediaPosition: 'left'
          });
        }
      }, {
        icon: isRTL ? _icons.pullLeft : _icons.pullRight,
        title: (0, _i18n.__)('Show media on right'),
        isActive: mediaPosition === 'right',
        onClick: function onClick() {
          return setAttributes({
            mediaPosition: 'right'
          });
        }
      }];

      var onVerticalAlignmentChange = function onVerticalAlignmentChange(alignment) {
        setAttributes({
          verticalAlignment: alignment
        });
      };

      return (0, _element.createElement)(_element.Fragment, null, mediaType === _blockEditor.MEDIA_TYPE_IMAGE && this.getControls(), (0, _element.createElement)(_blockEditor.BlockControls, null, (isMediaSelected || mediaType === _blockEditor.MEDIA_TYPE_VIDEO) && (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.Button, {
        label: (0, _i18n.__)('Edit media'),
        icon: _icons.replace,
        onClick: this.onReplaceMedia
      })), (!isMediaSelected || mediaType === _blockEditor.MEDIA_TYPE_VIDEO) && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ToolbarGroup, {
        controls: toolbarControls
      }), (0, _element.createElement)(_blockEditor.BlockVerticalAlignmentToolbar, {
        onChange: onVerticalAlignmentChange,
        value: verticalAlignment
      }))), (0, _element.createElement)(_reactNative.View, {
        style: containerStyles,
        onLayout: this.onLayoutChange
      }, (0, _element.createElement)(_reactNative.View, {
        style: [(shouldStack || !imageFill) && {
          width: widthString
        }, mediaContainerStyle]
      }, this.renderMediaArea(shouldStack)), (0, _element.createElement)(_reactNative.View, {
        style: _objectSpread({
          width: innerBlockWidthString
        }, innerBlockContainerStyle)
      }, (0, _element.createElement)(_blockEditor.InnerBlocks, {
        template: TEMPLATE,
        templateInsertUpdatesSelection: false
      }))));
    }
  }], [{
    key: "getDerivedStateFromProps",
    value: function getDerivedStateFromProps(props, state) {
      return {
        isMediaSelected: state.isMediaSelected && props.isSelected && !props.isAncestorSelected
      };
    }
  }]);
  return MediaTextEdit;
}(_element.Component);

var _default = (0, _compose.compose)((0, _blockEditor.withColors)('backgroundColor'), (0, _data.withSelect)(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getBlockRootClientId = _select.getBlockRootClientId,
      getBlockParents = _select.getBlockParents,
      getSettings = _select.getSettings;

  var parents = getBlockParents(clientId, true);
  var selectedBlockClientId = getSelectedBlockClientId();
  var isParentSelected = selectedBlockClientId && selectedBlockClientId === getBlockRootClientId(clientId);
  var isAncestorSelected = selectedBlockClientId && parents.includes(selectedBlockClientId);
  return {
    isSelected: selectedBlockClientId === clientId,
    isParentSelected: isParentSelected,
    isAncestorSelected: isAncestorSelected,
    isRTL: getSettings().isRTL
  };
}))(MediaTextEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map