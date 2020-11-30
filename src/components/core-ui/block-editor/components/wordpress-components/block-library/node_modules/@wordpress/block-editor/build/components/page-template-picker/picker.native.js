"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _data = require("@wordpress/data");

var _tooltip = _interopRequireDefault(require("./tooltip"));

var _reactNativeBridge = require("@wordpress/react-native-bridge");

var _reactNative = require("react-native");

var _button = _interopRequireDefault(require("./button"));

var _container = _interopRequireDefault(require("./container"));

var _defaultTemplates = _interopRequireDefault(require("./default-templates"));

var _preview = _interopRequireDefault(require("./preview"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
// Used to hide the picker if there's no enough space in the window
var PICKER_HEIGHT_OFFSET = 150;

var __experimentalPageTemplatePicker = function __experimentalPageTemplatePicker(_ref) {
  var _ref$templates = _ref.templates,
      templates = _ref$templates === void 0 ? (0, _defaultTemplates.default)() : _ref$templates,
      visible = _ref.visible;

  var _useDispatch = (0, _data.useDispatch)('core/editor'),
      editPost = _useDispatch.editPost;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/editor'),
        getEditedPostAttribute = _select.getEditedPostAttribute;

    return {
      title: getEditedPostAttribute('title')
    };
  }),
      title = _useSelect.title;

  var _useState = (0, _element.useState)(),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      templatePreview = _useState2[0],
      setTemplatePreview = _useState2[1];

  var _useState3 = (0, _element.useState)(visible),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      pickerVisible = _useState4[0],
      setPickerVisible = _useState4[1];

  var _useState5 = (0, _element.useState)(false),
      _useState6 = (0, _slicedToArray2.default)(_useState5, 2),
      tooltipVisible = _useState6[0],
      setTooltipVisible = _useState6[1];

  var contentOpacity = (0, _element.useRef)(new _reactNative.Animated.Value(0)).current;
  (0, _element.useEffect)(function () {
    if (shouldShowPicker() && visible && !pickerVisible) {
      setPickerVisible(true);
    }

    startPickerAnimation(visible);
    shouldShowTooltip();

    _reactNative.Keyboard.addListener('keyboardDidShow', onKeyboardDidShow);

    _reactNative.Keyboard.addListener('keyboardDidHide', onKeyboardDidHide);

    return function () {
      _reactNative.Keyboard.removeListener('keyboardDidShow', onKeyboardDidShow);

      _reactNative.Keyboard.removeListener('keyboardDidHide', onKeyboardDidHide);
    };
  }, [visible]);
  (0, _element.useEffect)(function () {
    if (tooltipVisible && templatePreview) {
      setTooltipVisible(false);
    }
  }, [templatePreview]);

  var onKeyboardDidShow = function onKeyboardDidShow() {
    if (visible) {
      startPickerAnimation(shouldShowPicker());
    }
  };

  var onKeyboardDidHide = function onKeyboardDidHide() {
    if (visible) {
      setPickerVisible(true);
      startPickerAnimation(true);
    }
  };

  var shouldShowPicker = function shouldShowPicker() {
    // On smaller devices on landscape we hide the picker
    // so it doesn't overlap with the editor's content
    var windowHeight = _reactNative.Dimensions.get('window').height;

    return PICKER_HEIGHT_OFFSET < windowHeight / 3;
  };

  var shouldShowTooltip = function shouldShowTooltip() {
    (0, _reactNativeBridge.requestStarterPageTemplatesTooltipShown)(function (tooltipShown) {
      if (!tooltipShown) {
        setTooltipVisible(true);
        (0, _reactNativeBridge.setStarterPageTemplatesTooltipShown)(true);
      }
    });
  };

  var onApply = function onApply() {
    editPost({
      title: title || templatePreview.name,
      blocks: templatePreview.blocks
    });
    (0, _reactNativeBridge.logUserEvent)(_reactNativeBridge.userEvents.editorSessionTemplateApply, {
      template: templatePreview.key
    });
    setTemplatePreview(undefined);
  };

  var onTooltipHidden = function onTooltipHidden() {
    setTooltipVisible(false);
  };

  var startPickerAnimation = function startPickerAnimation(isVisible) {
    _reactNative.Animated.timing(contentOpacity, {
      toValue: isVisible ? 1 : 0,
      duration: 300,
      useNativeDriver: true
    }).start(function () {
      if (!isVisible) {
        setPickerVisible(isVisible);
      }
    });
  };

  if (!pickerVisible) {
    return null;
  }

  return (0, _element.createElement)(_reactNative.Animated.View, {
    style: [{
      opacity: contentOpacity
    }]
  }, tooltipVisible && (0, _element.createElement)(_tooltip.default, {
    onTooltipHidden: onTooltipHidden
  }), (0, _element.createElement)(_container.default, null, templates.map(function (template) {
    return (0, _element.createElement)(_button.default, {
      key: template.key,
      icon: template.icon,
      label: template.name,
      onPress: function onPress() {
        (0, _reactNativeBridge.logUserEvent)(_reactNativeBridge.userEvents.editorSessionTemplatePreview, {
          template: template.key
        });
        setTemplatePreview(template);
      }
    });
  })), (0, _element.createElement)(_preview.default, {
    template: templatePreview,
    onDismiss: function onDismiss() {
      return setTemplatePreview(undefined);
    },
    onApply: onApply
  }));
};

var _default = __experimentalPageTemplatePicker;
exports.default = _default;
//# sourceMappingURL=picker.native.js.map