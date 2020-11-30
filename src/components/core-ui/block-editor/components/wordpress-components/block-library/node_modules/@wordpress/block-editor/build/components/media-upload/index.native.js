"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.MediaUpload = exports.OPTION_TAKE_PHOTO_OR_VIDEO = exports.OPTION_TAKE_PHOTO = exports.OPTION_TAKE_VIDEO = exports.MEDIA_TYPE_VIDEO = exports.MEDIA_TYPE_IMAGE = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _react = _interopRequireDefault(require("react"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _reactNativeBridge = require("@wordpress/react-native-bridge");

var _icons = require("@wordpress/icons");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var MEDIA_TYPE_IMAGE = 'image';
exports.MEDIA_TYPE_IMAGE = MEDIA_TYPE_IMAGE;
var MEDIA_TYPE_VIDEO = 'video';
exports.MEDIA_TYPE_VIDEO = MEDIA_TYPE_VIDEO;
var OPTION_TAKE_VIDEO = (0, _i18n.__)('Take a Video');
exports.OPTION_TAKE_VIDEO = OPTION_TAKE_VIDEO;
var OPTION_TAKE_PHOTO = (0, _i18n.__)('Take a Photo');
exports.OPTION_TAKE_PHOTO = OPTION_TAKE_PHOTO;
var OPTION_TAKE_PHOTO_OR_VIDEO = (0, _i18n.__)('Take a Photo or Video');
exports.OPTION_TAKE_PHOTO_OR_VIDEO = OPTION_TAKE_PHOTO_OR_VIDEO;

var MediaUpload = /*#__PURE__*/function (_React$Component) {
  (0, _inherits2.default)(MediaUpload, _React$Component);

  var _super = _createSuper(MediaUpload);

  function MediaUpload(props) {
    var _this;

    (0, _classCallCheck2.default)(this, MediaUpload);
    _this = _super.call(this, props);
    _this.onPickerPresent = _this.onPickerPresent.bind((0, _assertThisInitialized2.default)(_this));
    _this.onPickerSelect = _this.onPickerSelect.bind((0, _assertThisInitialized2.default)(_this));
    _this.getAllSources = _this.getAllSources.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      otherMediaOptions: []
    };
    return _this;
  }

  (0, _createClass2.default)(MediaUpload, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      var _this$props$allowedTy = this.props.allowedTypes,
          allowedTypes = _this$props$allowedTy === void 0 ? [] : _this$props$allowedTy;
      (0, _reactNativeBridge.getOtherMediaOptions)(allowedTypes, function (otherMediaOptions) {
        var otherMediaOptionsWithIcons = otherMediaOptions.map(function (option) {
          return _objectSpread(_objectSpread({}, option), {}, {
            types: allowedTypes,
            id: option.value
          });
        });

        _this2.setState({
          otherMediaOptions: otherMediaOptionsWithIcons
        });
      });
    }
  }, {
    key: "getAllSources",
    value: function getAllSources() {
      var cameraImageSource = {
        id: _reactNativeBridge.mediaSources.deviceCamera,
        // ID is the value sent to native
        value: _reactNativeBridge.mediaSources.deviceCamera + '-IMAGE',
        // This is needed to diferenciate image-camera from video-camera sources.
        label: (0, _i18n.__)('Take a Photo'),
        types: [MEDIA_TYPE_IMAGE],
        icon: _icons.capturePhoto
      };
      var cameraVideoSource = {
        id: _reactNativeBridge.mediaSources.deviceCamera,
        value: _reactNativeBridge.mediaSources.deviceCamera,
        label: (0, _i18n.__)('Take a Video'),
        types: [MEDIA_TYPE_VIDEO],
        icon: _icons.captureVideo
      };
      var deviceLibrarySource = {
        id: _reactNativeBridge.mediaSources.deviceLibrary,
        value: _reactNativeBridge.mediaSources.deviceLibrary,
        label: (0, _i18n.__)('Choose from device'),
        types: [MEDIA_TYPE_IMAGE, MEDIA_TYPE_VIDEO],
        icon: _icons.image
      };
      var siteLibrarySource = {
        id: _reactNativeBridge.mediaSources.siteMediaLibrary,
        value: _reactNativeBridge.mediaSources.siteMediaLibrary,
        label: (0, _i18n.__)('WordPress Media Library'),
        types: [MEDIA_TYPE_IMAGE, MEDIA_TYPE_VIDEO],
        icon: _icons.wordpress,
        mediaLibrary: true
      };
      var internalSources = [deviceLibrarySource, cameraImageSource, cameraVideoSource, siteLibrarySource];
      return internalSources.concat(this.state.otherMediaOptions);
    }
  }, {
    key: "getMediaOptionsItems",
    value: function getMediaOptionsItems() {
      var _this3 = this;

      var _this$props = this.props,
          _this$props$allowedTy2 = _this$props.allowedTypes,
          allowedTypes = _this$props$allowedTy2 === void 0 ? [] : _this$props$allowedTy2,
          __experimentalOnlyMediaLibrary = _this$props.__experimentalOnlyMediaLibrary;
      return this.getAllSources().filter(function (source) {
        return __experimentalOnlyMediaLibrary ? source.mediaLibrary : allowedTypes.some(function (allowedType) {
          return source.types.includes(allowedType);
        });
      }).map(function (source) {
        return _objectSpread(_objectSpread({}, source), {}, {
          icon: source.icon || _this3.getChooseFromDeviceIcon()
        });
      });
    }
  }, {
    key: "getChooseFromDeviceIcon",
    value: function getChooseFromDeviceIcon() {
      var _this$props$allowedTy3 = this.props.allowedTypes,
          allowedTypes = _this$props$allowedTy3 === void 0 ? [] : _this$props$allowedTy3;
      var isOneType = allowedTypes.length === 1;
      var isImage = isOneType && allowedTypes.includes(MEDIA_TYPE_IMAGE);
      var isVideo = isOneType && allowedTypes.includes(MEDIA_TYPE_VIDEO);

      if (isImage || !isOneType) {
        return _icons.image;
      } else if (isVideo) {
        return _icons.video;
      }
    }
  }, {
    key: "onPickerPresent",
    value: function onPickerPresent() {
      if (this.picker) {
        this.picker.presentPicker();
      }
    }
  }, {
    key: "onPickerSelect",
    value: function onPickerSelect(value) {
      var _this$props2 = this.props,
          _this$props2$allowedT = _this$props2.allowedTypes,
          allowedTypes = _this$props2$allowedT === void 0 ? [] : _this$props2$allowedT,
          onSelect = _this$props2.onSelect,
          _this$props2$multiple = _this$props2.multiple,
          multiple = _this$props2$multiple === void 0 ? false : _this$props2$multiple;
      var mediaSource = this.getAllSources().filter(function (source) {
        return source.value === value;
      }).shift();
      var types = allowedTypes.filter(function (type) {
        return mediaSource.types.includes(type);
      });
      (0, _reactNativeBridge.requestMediaPicker)(mediaSource.id, types, multiple, function (media) {
        if (multiple && media || media && media.id) {
          onSelect(media);
        }
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this4 = this;

      var _this$props3 = this.props,
          _this$props3$allowedT = _this$props3.allowedTypes,
          allowedTypes = _this$props3$allowedT === void 0 ? [] : _this$props3$allowedT,
          isReplacingMedia = _this$props3.isReplacingMedia;
      var isOneType = allowedTypes.length === 1;
      var isImage = isOneType && allowedTypes.includes(MEDIA_TYPE_IMAGE);
      var isVideo = isOneType && allowedTypes.includes(MEDIA_TYPE_VIDEO);
      var isImageOrVideo = allowedTypes.length === 2 && allowedTypes.includes(MEDIA_TYPE_IMAGE) && allowedTypes.includes(MEDIA_TYPE_VIDEO);
      var pickerTitle;

      if (isImage) {
        if (isReplacingMedia) {
          pickerTitle = (0, _i18n.__)('Replace image');
        } else {
          pickerTitle = (0, _i18n.__)('Choose image');
        }
      } else if (isVideo) {
        if (isReplacingMedia) {
          pickerTitle = (0, _i18n.__)('Replace video');
        } else {
          pickerTitle = (0, _i18n.__)('Choose video');
        }
      } else if (isImageOrVideo) {
        if (isReplacingMedia) {
          pickerTitle = (0, _i18n.__)('Replace image or video');
        } else {
          pickerTitle = (0, _i18n.__)('Choose image or video');
        }
      }

      var getMediaOptions = function getMediaOptions() {
        return (0, _element.createElement)(_components.Picker, {
          title: pickerTitle,
          hideCancelButton: true,
          ref: function ref(instance) {
            return _this4.picker = instance;
          },
          options: _this4.getMediaOptionsItems(),
          onChange: _this4.onPickerSelect
        });
      };

      return this.props.render({
        open: this.onPickerPresent,
        getMediaOptions: getMediaOptions
      });
    }
  }]);
  return MediaUpload;
}(_react.default.Component);

exports.MediaUpload = MediaUpload;
var _default = MediaUpload;
exports.default = _default;
//# sourceMappingURL=index.native.js.map