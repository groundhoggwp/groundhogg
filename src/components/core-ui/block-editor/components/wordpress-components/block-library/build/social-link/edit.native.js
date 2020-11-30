"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _data = require("@wordpress/data");

var _socialList = require("./social-list");

var _editor = _interopRequireDefault(require("./editor.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var ANIMATION_DELAY = 300;
var ANIMATION_DURATION = 400;
var linkSettingsOptions = {
  url: {
    label: (0, _i18n.__)('URL'),
    placeholder: (0, _i18n.__)('Add URL'),
    autoFocus: true
  },
  linkLabel: {
    label: (0, _i18n.__)('Link label'),
    placeholder: (0, _i18n.__)('None')
  },
  footer: {
    label: (0, _i18n.__)('Briefly describe the link to help screen reader user')
  }
};

var SocialLinkEdit = function SocialLinkEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      isSelected = _ref.isSelected,
      onFocus = _ref.onFocus,
      name = _ref.name;
  var url = attributes.url,
      _attributes$service = attributes.service,
      service = _attributes$service === void 0 ? name : _attributes$service;

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isLinkSheetVisible = _useState2[0],
      setIsLinkSheetVisible = _useState2[1];

  var _useState3 = (0, _element.useState)(!!url),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      hasUrl = _useState4[0],
      setHasUrl = _useState4[1];

  var activeIcon = _editor.default["wp-social-link-".concat(service)] || _editor.default["wp-social-link"];

  var inactiveIcon = (0, _compose.usePreferredColorSchemeStyle)(_editor.default.inactiveIcon, _editor.default.inactiveIconDark);
  var animatedValue = (0, _element.useRef)(new _reactNative.Animated.Value(0)).current;
  var IconComponent = (0, _socialList.getIconBySite)(service)();
  var socialLinkName = (0, _socialList.getNameBySite)(service); // When new social icon is added link sheet is opened automatically

  (0, _element.useEffect)(function () {
    if (isSelected && !url) {
      setIsLinkSheetVisible(true);
    }
  }, []);
  (0, _element.useEffect)(function () {
    if (!url) {
      setHasUrl(false);
      animatedValue.setValue(0);
    } else if (url) {
      animateColors();
    }
  }, [url]);
  var interpolationColors = {
    backgroundColor: animatedValue.interpolate({
      inputRange: [0, 1],
      outputRange: [inactiveIcon.backgroundColor, activeIcon.backgroundColor]
    }),
    color: animatedValue.interpolate({
      inputRange: [0, 1],
      outputRange: [inactiveIcon.color, activeIcon.color]
    }),
    stroke: ''
  };

  var _ref2 = hasUrl ? activeIcon : interpolationColors,
      backgroundColor = _ref2.backgroundColor,
      color = _ref2.color,
      stroke = _ref2.stroke;

  function animateColors() {
    _reactNative.Animated.sequence([_reactNative.Animated.delay(ANIMATION_DELAY), _reactNative.Animated.timing(animatedValue, {
      toValue: 1,
      duration: ANIMATION_DURATION,
      easing: _reactNative.Easing.circle
    })]).start(function () {
      return setHasUrl(true);
    });
  }

  function onCloseSettingsSheet() {
    setIsLinkSheetVisible(false);
  }

  function onOpenSettingsSheet() {
    setIsLinkSheetVisible(true);
  }

  function onEmptyURL() {
    animatedValue.setValue(0);
    setHasUrl(false);
  }

  function onIconPress() {
    if (isSelected) {
      setIsLinkSheetVisible(true);
    } else {
      onFocus();
    }
  }

  var accessibilityHint = url ? (0, _i18n.sprintf)( // translators: %s: social link name e.g: "Instagram".
  (0, _i18n.__)('%s has URL set'), socialLinkName) : (0, _i18n.sprintf)( // translators: %s: social link name e.g: "Instagram".
  (0, _i18n.__)('%s has no URL set'), socialLinkName);
  return (0, _element.createElement)(_reactNative.View, null, isSelected && (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarButton, {
    title: (0, _i18n.sprintf)( // translators: %s: social link name e.g: "Instagram".
    (0, _i18n.__)('Add link to %s'), socialLinkName),
    icon: _icons.link,
    onClick: onOpenSettingsSheet,
    isActive: url
  }))), (0, _element.createElement)(_components.LinkSettings, {
    isVisible: isLinkSheetVisible,
    attributes: attributes,
    onEmptyURL: onEmptyURL,
    onClose: onCloseSettingsSheet,
    setAttributes: setAttributes,
    options: linkSettingsOptions,
    withBottomSheet: true
  }), (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
    onPress: onIconPress,
    accessibilityRole: 'button',
    accessibilityLabel: (0, _i18n.sprintf)( // translators: %s: social link name e.g: "Instagram".
    (0, _i18n.__)('%s social icon'), socialLinkName),
    accessibilityHint: accessibilityHint
  }, (0, _element.createElement)(_reactNative.Animated.View, {
    style: [_editor.default.iconContainer, {
      backgroundColor: backgroundColor
    }]
  }, (0, _element.createElement)(_icons.Icon, {
    animated: true,
    icon: IconComponent,
    style: {
      stroke: stroke,
      color: color
    }
  }))));
};

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref3) {
  var clientId = _ref3.clientId;

  var _select = select('core/block-editor'),
      getBlock = _select.getBlock;

  var block = getBlock(clientId);
  var name = block === null || block === void 0 ? void 0 : block.name.substring(17);
  return {
    name: name
  };
})])(SocialLinkEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map