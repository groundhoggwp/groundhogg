import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import React from 'react';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { Picker } from '@wordpress/components';
import { getOtherMediaOptions, requestMediaPicker, mediaSources } from '@wordpress/react-native-bridge';
import { capturePhoto, captureVideo, image, video, wordpress } from '@wordpress/icons';
export var MEDIA_TYPE_IMAGE = 'image';
export var MEDIA_TYPE_VIDEO = 'video';
export var OPTION_TAKE_VIDEO = __('Take a Video');
export var OPTION_TAKE_PHOTO = __('Take a Photo');
export var OPTION_TAKE_PHOTO_OR_VIDEO = __('Take a Photo or Video');
export var MediaUpload = /*#__PURE__*/function (_React$Component) {
  _inherits(MediaUpload, _React$Component);

  var _super = _createSuper(MediaUpload);

  function MediaUpload(props) {
    var _this;

    _classCallCheck(this, MediaUpload);

    _this = _super.call(this, props);
    _this.onPickerPresent = _this.onPickerPresent.bind(_assertThisInitialized(_this));
    _this.onPickerSelect = _this.onPickerSelect.bind(_assertThisInitialized(_this));
    _this.getAllSources = _this.getAllSources.bind(_assertThisInitialized(_this));
    _this.state = {
      otherMediaOptions: []
    };
    return _this;
  }

  _createClass(MediaUpload, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      var _this$props$allowedTy = this.props.allowedTypes,
          allowedTypes = _this$props$allowedTy === void 0 ? [] : _this$props$allowedTy;
      getOtherMediaOptions(allowedTypes, function (otherMediaOptions) {
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
        id: mediaSources.deviceCamera,
        // ID is the value sent to native
        value: mediaSources.deviceCamera + '-IMAGE',
        // This is needed to diferenciate image-camera from video-camera sources.
        label: __('Take a Photo'),
        types: [MEDIA_TYPE_IMAGE],
        icon: capturePhoto
      };
      var cameraVideoSource = {
        id: mediaSources.deviceCamera,
        value: mediaSources.deviceCamera,
        label: __('Take a Video'),
        types: [MEDIA_TYPE_VIDEO],
        icon: captureVideo
      };
      var deviceLibrarySource = {
        id: mediaSources.deviceLibrary,
        value: mediaSources.deviceLibrary,
        label: __('Choose from device'),
        types: [MEDIA_TYPE_IMAGE, MEDIA_TYPE_VIDEO],
        icon: image
      };
      var siteLibrarySource = {
        id: mediaSources.siteMediaLibrary,
        value: mediaSources.siteMediaLibrary,
        label: __('WordPress Media Library'),
        types: [MEDIA_TYPE_IMAGE, MEDIA_TYPE_VIDEO],
        icon: wordpress,
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
        return image;
      } else if (isVideo) {
        return video;
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
      requestMediaPicker(mediaSource.id, types, multiple, function (media) {
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
          pickerTitle = __('Replace image');
        } else {
          pickerTitle = __('Choose image');
        }
      } else if (isVideo) {
        if (isReplacingMedia) {
          pickerTitle = __('Replace video');
        } else {
          pickerTitle = __('Choose video');
        }
      } else if (isImageOrVideo) {
        if (isReplacingMedia) {
          pickerTitle = __('Replace image or video');
        } else {
          pickerTitle = __('Choose image or video');
        }
      }

      var getMediaOptions = function getMediaOptions() {
        return createElement(Picker, {
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
}(React.Component);
export default MediaUpload;
//# sourceMappingURL=index.native.js.map